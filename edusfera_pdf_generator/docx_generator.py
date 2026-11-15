#!/usr/bin/env python3
"""
EDUSFERA Word (.docx) Exam Generator
Generates clean, editable, academic Microsoft Word (.docx) documents
from Edusfera exam JSON data using only Python standard library (zipfile + xml.etree).
Requires zero external dependencies.
"""

from __future__ import annotations

import argparse
import html
import json
import os
import re
from datetime import datetime, timezone
from pathlib import Path
from xml.etree import ElementTree as ET
from zipfile import ZIP_DEFLATED, ZipFile

W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
R_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
CP_NS = "http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
DC_NS = "http://purl.org/dc/elements/1.1/"
DCTERMS_NS = "http://purl.org/dc/terms/"
XSI_NS = "http://www.w3.org/2001/XMLSchema-instance"

ET.register_namespace("w", W_NS)
ET.register_namespace("r", R_NS)
ET.register_namespace("cp", CP_NS)
ET.register_namespace("dc", DC_NS)
ET.register_namespace("dcterms", DCTERMS_NS)
ET.register_namespace("xsi", XSI_NS)


def qn(tag: str) -> str:
    prefix, local = tag.split(":") if ":" in tag else ("w", tag)
    ns_map = {
        "w": W_NS,
        "r": R_NS,
        "cp": CP_NS,
        "dc": DC_NS,
        "dcterms": DCTERMS_NS,
        "xsi": XSI_NS,
    }
    return f"{{{ns_map[prefix]}}}{local}"


class ExamDocxBuilder:
    def __init__(self, title: str = "Проверочная работа", subject: str = "Математика"):
        self.title = title
        self.subject = subject
        self.doc = ET.Element(qn("w:document"))
        self.body = ET.SubElement(self.doc, qn("w:body"))

    def add_paragraph(
        self,
        text: str = "",
        style: str | None = None,
        bold: bool = False,
        italic: bool = False,
        font_size_pt: float = 11.0,
        font_name: str = "Times New Roman",
        color: str | None = None,
        align: str = "left",
        space_before_pt: float = 0,
        space_after_pt: float = 3,
        line_spacing: float = 1.15,
    ) -> ET.Element:
        p = ET.SubElement(self.body, qn("w:p"))
        p_pr = ET.SubElement(p, qn("w:pPr"))

        if style:
            ET.SubElement(p_pr, qn("w:pStyle"), {qn("w:val"): style})

        align_map = {"left": "left", "center": "center", "right": "right", "justify": "both"}
        ET.SubElement(p_pr, qn("w:jc"), {qn("w:val"): align_map.get(align, "left")})

        sp_before = str(int(space_before_pt * 20))
        sp_after = str(int(space_after_pt * 20))
        line_val = str(int(line_spacing * 240))
        ET.SubElement(p_pr, qn("w:spacing"), {
            qn("w:before"): sp_before,
            qn("w:after"): sp_after,
            qn("w:line"): line_val,
            qn("w:lineRule"): "auto",
        })

        if text:
            self._append_run(p, text, bold, italic, font_size_pt, font_name, color)
        return p

    def _append_run(
        self,
        p: ET.Element,
        text: str,
        bold: bool = False,
        italic: bool = False,
        font_size_pt: float = 11.0,
        font_name: str = "Times New Roman",
        color: str | None = None,
    ):
        r = ET.SubElement(p, qn("w:r"))
        r_pr = ET.SubElement(r, qn("w:rPr"))

        ET.SubElement(r_pr, qn("w:rFonts"), {
            qn("w:ascii"): font_name,
            qn("w:hAnsi"): font_name,
            qn("w:cs"): font_name,
        })
        sz_val = str(int(font_size_pt * 2))
        ET.SubElement(r_pr, qn("w:sz"), {qn("w:val"): sz_val})
        ET.SubElement(r_pr, qn("w:szCs"), {qn("w:val"): sz_val})

        if bold:
            ET.SubElement(r_pr, qn("w:b"))
        if italic:
            ET.SubElement(r_pr, qn("w:i"))
        if color:
            ET.SubElement(r_pr, qn("w:color"), {qn("w:val"): color.lstrip("#")})

        t = ET.SubElement(r, qn("w:t"))
        if text.startswith(" ") or text.endswith(" "):
            t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
        t.text = text

    def add_page_break(self):
        p = ET.SubElement(self.body, qn("w:p"))
        r = ET.SubElement(p, qn("w:r"))
        ET.SubElement(r, qn("w:br"), {qn("w:type"): "page"})

    def add_horizontal_divider(self, text: str = "Линия отреза"):
        p = ET.SubElement(self.body, qn("w:p"))
        p_pr = ET.SubElement(p, qn("w:pPr"))
        ET.SubElement(p_pr, qn("w:jc"), {qn("w:val"): "center"})
        ET.SubElement(p_pr, qn("w:spacing"), {qn("w:before"): "120", qn("w:after"): "120"})
        divider = f"- - - - - - - - - - [ {text} ] - - - - - - - - - -"
        self._append_run(p, divider, italic=True, font_size_pt=8.5, color="64748B")

    def build_package(self) -> bytes:
        # Final section properties (A4 margins: 20mm top/bottom, 20mm left/right)
        sect_pr = ET.SubElement(self.body, qn("w:sectPr"))
        ET.SubElement(sect_pr, qn("w:pgSz"), {qn("w:w"): "11906", qn("w:h"): "16838"})  # A4: 210mm x 297mm
        ET.SubElement(sect_pr, qn("w:pgMar"), {
            qn("w:top"): "1134",    # 20mm
            qn("w:right"): "1134",  # 20mm
            qn("w:bottom"): "1134", # 20mm
            qn("w:left"): "1134",   # 20mm
            qn("w:header"): "708",
            qn("w:footer"): "708",
        })

        content_types = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">\n'
            '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>\n'
            '  <Default Extension="xml" ContentType="application/xml"/>\n'
            '  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>\n'
            '  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>\n'
            '  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>\n'
            '  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>\n'
            '</Types>'
        )

        root_rels = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n'
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>\n'
            '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>\n'
            '  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>\n'
            '</Relationships>'
        )

        doc_rels = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n'
            '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>\n'
            '</Relationships>'
        )

        styles_xml = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">\n'
            '  <w:docDefaults>\n'
            '    <w:rPrDefault>\n'
            '      <w:rPr>\n'
            '        <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>\n'
            '        <w:sz w:val="22"/>\n'
            '        <w:szCs w:val="22"/>\n'
            '        <w:lang w:val="ru-RU"/>\n'
            '      </w:rPr>\n'
            '    </w:rPrDefault>\n'
            '  </w:docDefaults>\n'
            '</w:styles>'
        )

        now_iso = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
        core_xml = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            f'<cp:coreProperties xmlns:cp="{CP_NS}" xmlns:dc="{DC_NS}" xmlns:dcterms="{DCTERMS_NS}" xmlns:xsi="{XSI_NS}">\n'
            f'  <dc:title>{html.escape(self.title)}</dc:title>\n'
            f'  <dc:subject>{html.escape(self.subject)}</dc:subject>\n'
            '  <dc:creator>EDUSFERA.BY</dc:creator>\n'
            f'  <dcterms:created xsi:type="dcterms:W3CDTF">{now_iso}</dcterms:created>\n'
            f'  <dcterms:modified xsi:type="dcterms:W3CDTF">{now_iso}</dcterms:modified>\n'
            '</cp:coreProperties>'
        )

        app_xml = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">\n'
            '  <Application>EDUSFERA Academic Exam Engine</Application>\n'
            '</Properties>'
        )

        doc_xml = ET.tostring(self.doc, encoding="utf-8", xml_declaration=True)

        from io import BytesIO
        buf = BytesIO()
        with ZipFile(buf, "w", compression=ZIP_DEFLATED) as zf:
            zf.writestr("[Content_Types].xml", content_types)
            zf.writestr("_rels/.rels", root_rels)
            zf.writestr("word/_rels/document.xml.rels", doc_rels)
            zf.writestr("word/document.xml", doc_xml)
            zf.writestr("word/styles.xml", styles_xml)
            zf.writestr("docProps/core.xml", core_xml)
            zf.writestr("docProps/app.xml", app_xml)

        return buf.getvalue()


def convert_exam_json_to_docx(json_data: dict, out_path: Path):
    meta = json_data.get("meta", {})
    variants = json_data.get("variants", [])
    master_table = json_data.get("master_table", [])

    title = meta.get("title") or f"Проверочная работа: {meta.get('topic', 'Тема')}"
    subject = meta.get("subject", "Математика")
    grade = meta.get("grade", "7 класс")
    builder = ExamDocxBuilder(title=title, subject=subject)

    for v_idx, variant in enumerate(variants):
        var_num = variant.get("number", v_idx + 1)
        var_title = variant.get("title", f"Вариант {var_num}")

        # Header Block
        p_hdr = builder.add_paragraph(align="left", space_after_pt=1)
        builder._append_run(p_hdr, "EDUSFERA ", bold=True, font_size_pt=14, color="0F172A")
        builder._append_run(p_hdr, " • Учебно-методический комплекс (edusfera.by)", italic=True, font_size_pt=9, color="64748B")

        # Subject & Topic
        builder.add_paragraph(
            f"{subject}. {title}",
            bold=True,
            font_size_pt=13,
            space_before_pt=2,
            space_after_pt=2,
        )

        # Variant Box & Student Credentials
        p_student = builder.add_paragraph(align="left", space_before_pt=2, space_after_pt=6)
        builder._append_run(p_student, f"[{var_title.upper()}]  ", bold=True, font_size_pt=11, color="000000")
        builder._append_run(p_student, f"Учащийся: _______________________  Класс: {grade}  Дата: ________", font_size_pt=10, color="334155")

        # Part A Tasks
        part_a = variant.get("part_a", [])
        part_b = variant.get("part_b", [])

        if part_a:
            builder.add_paragraph("Часть А (Задания с выбором ответа):", bold=True, italic=True, font_size_pt=10, color="475569", space_before_pt=4, space_after_pt=3)
            for t_idx, task in enumerate(part_a):
                t_num = task.get("number") or str(t_idx + 1)
                t_prompt = task.get("prompt") or task.get("condition") or ""
                # Strip LaTeX markup or simple math for clean Word display
                t_clean = re.sub(r'\\frac\{([^}]+)\}\{([^}]+)\}', r'(\1)/(\2)', t_prompt)
                t_clean = t_clean.replace('$', '').replace(r'\cdot', '·').replace(r'\le', '≤').replace(r'\ge', '≥')

                p_task = builder.add_paragraph(space_before_pt=3, space_after_pt=2)
                builder._append_run(p_task, f"{t_num}. ", bold=True, font_size_pt=11)
                builder._append_run(p_task, t_clean, font_size_pt=11)

                options = task.get("options", [])
                if options:
                    p_opts = builder.add_paragraph(space_before_pt=1, space_after_pt=4)
                    opt_letters = ["А", "Б", "В", "Г", "Д"]
                    opt_strs = []
                    for o_idx, opt in enumerate(options):
                        let = opt.get("letter") or (opt_letters[o_idx] if o_idx < len(opt_letters) else str(o_idx + 1))
                        txt = str(opt.get("text", "")).replace('$', '')
                        opt_strs.append(f"[ {let} ]  {txt}")
                    builder._append_run(p_opts, "        ".join(opt_strs), font_size_pt=10)

        # Part B Tasks
        if part_b:
            builder.add_paragraph("Часть Б (Задания с кратким числовым ответом):", bold=True, italic=True, font_size_pt=10, color="475569", space_before_pt=4, space_after_pt=3)
            for t_idx, task in enumerate(part_b):
                t_num = task.get("number") or str(len(part_a) + t_idx + 1)
                t_prompt = task.get("prompt") or task.get("condition") or ""
                t_clean = re.sub(r'\\frac\{([^}]+)\}\{([^}]+)\}', r'(\1)/(\2)', t_prompt)
                t_clean = t_clean.replace('$', '').replace(r'\cdot', '·')

                p_task = builder.add_paragraph(space_before_pt=3, space_after_pt=2)
                builder._append_run(p_task, f"{t_num}. ", bold=True, font_size_pt=11)
                builder._append_run(p_task, t_clean, font_size_pt=11)

                p_ans_field = builder.add_paragraph(space_before_pt=1, space_after_pt=4)
                builder._append_run(p_ans_field, "Ответ ученика: [                                         ]", italic=True, font_size_pt=9.5, color="64748B")

        # Answer blank line
        builder.add_horizontal_divider("Линия отреза для проверки учителем")
        p_blank = builder.add_paragraph(space_before_pt=2, space_after_pt=6)
        builder._append_run(p_blank, f"БЛАНК ОТВЕТОВ ({var_title}): ", bold=True, font_size_pt=10)
        blank_cols = []
        for i in range(1, len(part_a) + len(part_b) + 1):
            blank_cols.append(f"№{i}: [   ]")
        builder._append_run(p_blank, "   ".join(blank_cols) + "    Отметка: [     / 10 ]", font_size_pt=9.5)

        # Page break before next variant or teacher key
        if v_idx < len(variants) - 1 or master_table:
            builder.add_page_break()

    # Teacher Keys Section
    p_t_hdr = builder.add_paragraph(align="center", space_before_pt=4, space_after_pt=2)
    builder._append_run(p_t_hdr, "КЛЮЧИ И ОТВЕТЫ ДЛЯ ПРЕПОДАВАТЕЛЯ (НЕ ВЫДАВАТЬ УЧЕНИКАМ)", bold=True, font_size_pt=12, color="DC2626")

    p_t_sub = builder.add_paragraph(align="center", space_after_pt=8)
    builder._append_run(p_t_sub, f"Предмет: {subject} • {title} • Стандарты экзаменов Беларуси 2026", italic=True, font_size_pt=9.5, color="475569")

    # Table of Answers
    for v_idx, variant in enumerate(variants):
        var_num = variant.get("number", v_idx + 1)
        var_title = variant.get("title", f"Вариант {var_num}")
        p_v_title = builder.add_paragraph(space_before_pt=4, space_after_pt=2)
        builder._append_run(p_v_title, f"● {var_title}:", bold=True, font_size_pt=11, color="0F172A")

        all_v_tasks = variant.get("part_a", []) + variant.get("part_b", [])
        for t_idx, t in enumerate(all_v_tasks):
            t_num = t.get("number") or str(t_idx + 1)
            t_ans = t.get("correct") or "—"
            t_expl = t.get("explanation") or ""
            p_ans_line = builder.add_paragraph(space_before_pt=1, space_after_pt=1)
            builder._append_run(p_ans_line, f"    Задание {t_num}: ", bold=True, font_size_pt=10.5)
            builder._append_run(p_ans_line, f"Ответ: {t_ans}", bold=True, font_size_pt=10.5, color="16A34A")
            if t_expl:
                builder._append_run(p_ans_line, f" — {t_expl}", italic=True, font_size_pt=9.5, color="475569")

    docx_bytes = builder.build_package()
    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_bytes(docx_bytes)
    return out_path


def main():
    parser = argparse.ArgumentParser(description="EDUSFERA Exam Docx Generator")
    parser.add_argument("--json", required=True, help="Input exam JSON file")
    parser.add_argument("--out", required=True, help="Output .docx file path")
    args = parser.parse_args()

    json_path = Path(args.json)
    out_path = Path(args.out)
    data = json.loads(json_path.read_text(encoding="utf-8"))
    res = convert_exam_json_to_docx(data, out_path)
    print(f"DOCX_EXPORT_OK:{res}")


if __name__ == "__main__":
    main()
