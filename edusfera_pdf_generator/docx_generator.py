#!/usr/bin/env python3
"""
EDUSFERA Word (.docx) Exam Generator
Generates clean, academic, editable Microsoft Word (.docx) documents
from Edusfera exam JSON data using only Python standard library (zipfile + xml.etree).
Requires zero external dependencies.

Features:
- Native Word 2016+ modern OpenXML (compatibilityMode=15) - NO 'режим совместимости'
- Native Office Math (OMML <m:oMath>) for vertical fractions (\\frac{a}{b}), roots, superscripts
- <w:noProof/> on all runs to suppress spell-checking squiggly underlines
- Desk-paired 2-up layout for short tests (<= 5 questions) with scissors cut line
- Clean Word tables for Headers, Student lines, Part A option chips, and Student answer sheets
- Teacher master answer key table on page 2
"""

from __future__ import annotations

import argparse
import html
import json
import os
import re
from datetime import datetime, timezone
from io import BytesIO
from pathlib import Path
from xml.etree import ElementTree as ET
from zipfile import ZIP_DEFLATED, ZipFile

# XML Namespaces
W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
M_NS = "http://schemas.openxmlformats.org/officeDocument/2006/math"
R_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
CP_NS = "http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
DC_NS = "http://purl.org/dc/elements/1.1/"
DCTERMS_NS = "http://purl.org/dc/terms/"
XSI_NS = "http://www.w3.org/2001/XMLSchema-instance"

ET.register_namespace("w", W_NS)
ET.register_namespace("m", M_NS)
ET.register_namespace("r", R_NS)
ET.register_namespace("cp", CP_NS)
ET.register_namespace("dc", DC_NS)
ET.register_namespace("dcterms", DCTERMS_NS)
ET.register_namespace("xsi", XSI_NS)


def qn_w(tag: str) -> str:
    return f"{{{W_NS}}}{tag}"


def qn_m(tag: str) -> str:
    return f"{{{M_NS}}}{tag}"


class ExamDocxBuilder:
    def __init__(self, title: str = "Проверочная работа", subject: str = "Математика"):
        self.title = title
        self.subject = subject
        self.doc = ET.Element(qn_w("document"))
        self.body = ET.SubElement(self.doc, qn_w("body"))

    def add_paragraph(
        self,
        text: str = "",
        bold: bool = False,
        italic: bool = False,
        font_size_pt: float = 10.5,
        font_name: str = "Times New Roman",
        color: str | None = None,
        align: str = "left",
        space_before_pt: float = 0,
        space_after_pt: float = 3,
        line_spacing: float = 1.15,
        parent: ET.Element | None = None,
    ) -> ET.Element:
        target = parent if parent is not None else self.body
        p = ET.SubElement(target, qn_w("p"))
        p_pr = ET.SubElement(p, qn_w("pPr"))

        align_map = {"left": "left", "center": "center", "right": "right", "justify": "both"}
        if align != "left":
            ET.SubElement(p_pr, qn_w("jc"), {qn_w("val"): align_map.get(align, "left")})

        sp_before = str(int(space_before_pt * 20))
        sp_after = str(int(space_after_pt * 20))
        line_val = str(int(line_spacing * 240))
        ET.SubElement(p_pr, qn_w("spacing"), {
            qn_w("before"): sp_before,
            qn_w("after"): sp_after,
            qn_w("line"): line_val,
            qn_w("lineRule"): "auto",
        })

        if text:
            self.append_text_with_math(p, text, bold, italic, font_size_pt, font_name, color)
        return p

    def append_run(
        self,
        p: ET.Element,
        text: str,
        bold: bool = False,
        italic: bool = False,
        font_size_pt: float = 10.5,
        font_name: str = "Times New Roman",
        color: str | None = None,
    ) -> ET.Element:
        r = ET.SubElement(p, qn_w("r"))
        r_pr = ET.SubElement(r, qn_w("rPr"))

        # Suppress spell-check squiggly lines
        ET.SubElement(r_pr, qn_w("noProof"))

        ET.SubElement(r_pr, qn_w("rFonts"), {
            qn_w("ascii"): font_name,
            qn_w("hAnsi"): font_name,
            qn_w("cs"): font_name,
        })
        sz_val = str(int(font_size_pt * 2))
        ET.SubElement(r_pr, qn_w("sz"), {qn_w("val"): sz_val})
        ET.SubElement(r_pr, qn_w("szCs"), {qn_w("val"): sz_val})

        if bold:
            ET.SubElement(r_pr, qn_w("b"))
        if italic:
            ET.SubElement(r_pr, qn_w("i"))
        if color:
            ET.SubElement(r_pr, qn_w("color"), {qn_w("val"): color.lstrip("#")})

        t = ET.SubElement(r, qn_w("t"))
        if text.startswith(" ") or text.endswith(" ") or "  " in text:
            t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
        t.text = text
        return r

    def append_text_with_math(
        self,
        p: ET.Element,
        text: str,
        bold: bool = False,
        italic: bool = False,
        font_size_pt: float = 10.5,
        font_name: str = "Times New Roman",
        color: str | None = None,
    ):
        """
        Parses text and renders LaTeX fractions (\\frac{a}{b}) as native Word OMML (<m:oMath><m:f>...).
        Replaces standard LaTeX math symbols with clean typography.
        """
        if not text:
            return

        # Pre-clean symbols
        text = text.replace("$", "")
        text = text.replace(r"\cdot", " · ")
        text = text.replace(r"\le", " ≤ ")
        text = text.replace(r"\ge", " ≥ ")
        text = text.replace(r"\ne", " ≠ ")
        text = text.replace(r"\pm", " ± ")
        text = text.replace(r"\approx", " ≈ ")
        text = text.replace(r"\times", " × ")

        frac_pattern = re.compile(r"\\frac\{([^{}]+)\}\{([^{}]+)\}")
        last_idx = 0

        for match in frac_pattern.finditer(text):
            start, end = match.span()
            if start > last_idx:
                chunk = text[last_idx:start]
                self.append_run(p, chunk, bold, italic, font_size_pt, font_name, color)

            num_str, den_str = match.group(1).strip(), match.group(2).strip()

            # Word OpenXML Math fraction
            omath = ET.SubElement(p, qn_m("oMath"))
            f = ET.SubElement(omath, qn_m("f"))
            fpr = ET.SubElement(f, qn_m("fPr"))
            ET.SubElement(fpr, qn_m("type"), {qn_m("val"): "bar"})

            # Numerator
            num_el = ET.SubElement(f, qn_m("num"))
            r_num = ET.SubElement(num_el, qn_m("r"))
            t_num = ET.SubElement(r_num, qn_m("t"))
            t_num.text = num_str

            # Denominator
            den_el = ET.SubElement(f, qn_m("den"))
            r_den = ET.SubElement(den_el, qn_m("r"))
            t_den = ET.SubElement(r_den, qn_m("t"))
            t_den.text = den_str

            last_idx = end

        if last_idx < len(text):
            chunk = text[last_idx:]
            self.append_run(p, chunk, bold, italic, font_size_pt, font_name, color)

    def add_page_break(self):
        p = ET.SubElement(self.body, qn_w("p"))
        r = ET.SubElement(p, qn_w("r"))
        ET.SubElement(r, qn_w("br"), {qn_w("type"): "page"})

    def add_horizontal_divider(self, text: str = "Линия отреза (раздать по вариантам)"):
        p = ET.SubElement(self.body, qn_w("p"))
        p_pr = ET.SubElement(p, qn_w("pPr"))
        ET.SubElement(p_pr, qn_w("jc"), {qn_w("val"): "center"})
        ET.SubElement(p_pr, qn_w("spacing"), {qn_w("before"): "140", qn_w("after"): "140"})
        divider = f"- - - - - - - - - - - - - - - - ✂  {text}  - - - - - - - - - - - - - - - -"
        self.append_run(p, divider, italic=True, font_size_pt=8.5, color="94A3B8")

    def create_table(
        self,
        col_widths: list[int],
        total_width: int = 10200,
        borders: dict | None = None,
        parent: ET.Element | None = None,
    ) -> ET.Element:
        target = parent if parent is not None else self.body
        tbl = ET.SubElement(target, qn_w("tbl"))
        tbl_pr = ET.SubElement(tbl, qn_w("tblPr"))
        ET.SubElement(tbl_pr, qn_w("tblW"), {qn_w("w"): str(total_width), qn_w("type"): "dxa"})
        ET.SubElement(tbl_pr, qn_w("tblLayout"), {qn_w("type"): "fixed"})

        # Borders
        tbl_borders = ET.SubElement(tbl_pr, qn_w("tblBorders"))
        border_names = ["top", "left", "bottom", "right", "insideH", "insideV"]
        for b_name in border_names:
            b_spec = borders.get(b_name) if borders else None
            if b_spec:
                ET.SubElement(tbl_borders, qn_w(b_name), {
                    qn_w("val"): b_spec.get("val", "single"),
                    qn_w("sz"): str(b_spec.get("sz", 4)),
                    qn_w("space"): "0",
                    qn_w("color"): b_spec.get("color", "CBD5E1"),
                })
            else:
                ET.SubElement(tbl_borders, qn_w(b_name), {
                    qn_w("val"): "none",
                    qn_w("sz"): "0",
                    qn_w("space"): "0",
                    qn_w("color"): "auto",
                })

        # Cell margins
        tbl_cell_mar = ET.SubElement(tbl_pr, qn_w("tblCellMar"))
        ET.SubElement(tbl_cell_mar, qn_w("top"), {qn_w("w"): "40", qn_w("type"): "dxa"})
        ET.SubElement(tbl_cell_mar, qn_w("bottom"), {qn_w("w"): "40", qn_w("type"): "dxa"})
        ET.SubElement(tbl_cell_mar, qn_w("left"), {qn_w("w"): "80", qn_w("type"): "dxa"})
        ET.SubElement(tbl_cell_mar, qn_w("right"), {qn_w("w"): "80", qn_w("type"): "dxa"})

        tbl_grid = ET.SubElement(tbl, qn_w("tblGrid"))
        for w in col_widths:
            ET.SubElement(tbl_grid, qn_w("gridCol"), {qn_w("w"): str(w)})

        return tbl

    def add_table_row(self, tbl: ET.Element, min_height_pt: float | None = None) -> ET.Element:
        tr = ET.SubElement(tbl, qn_w("tr"))
        tr_pr = ET.SubElement(tr, qn_w("trPr"))
        ET.SubElement(tr_pr, qn_w("cantSplit"))
        if min_height_pt is not None:
            ET.SubElement(tr_pr, qn_w("trHeight"), {
                qn_w("val"): str(int(min_height_pt * 20)),
                qn_w("hRule"): "atLeast",
            })
        return tr

    def add_table_cell(
        self,
        tr: ET.Element,
        width: int,
        bg_color: str | None = None,
        bottom_border: dict | None = None,
        all_borders: dict | None = None,
        v_align: str = "center",
    ) -> tuple[ET.Element, ET.Element]:
        tc = ET.SubElement(tr, qn_w("tc"))
        tc_pr = ET.SubElement(tc, qn_w("tcPr"))
        ET.SubElement(tc_pr, qn_w("tcW"), {qn_w("w"): str(width), qn_w("type"): "dxa"})

        if v_align:
            ET.SubElement(tc_pr, qn_w("vAlign"), {qn_w("val"): v_align})

        if bg_color:
            ET.SubElement(tc_pr, qn_w("shd"), {
                qn_w("val"): "clear",
                qn_w("color"): "auto",
                qn_w("fill"): bg_color.lstrip("#"),
            })

        if all_borders:
            tc_b = ET.SubElement(tc_pr, qn_w("tcBorders"))
            for side in ["top", "left", "bottom", "right"]:
                spec = all_borders.get(side)
                if spec:
                    ET.SubElement(tc_b, qn_w(side), {
                        qn_w("val"): spec.get("val", "single"),
                        qn_w("sz"): str(spec.get("sz", 4)),
                        qn_w("space"): "0",
                        qn_w("color"): spec.get("color", "CBD5E1"),
                    })
        elif bottom_border:
            tc_b = ET.SubElement(tc_pr, qn_w("tcBorders"))
            ET.SubElement(tc_b, qn_w("bottom"), {
                qn_w("val"): bottom_border.get("val", "single"),
                qn_w("sz"): str(bottom_border.get("sz", 6)),
                qn_w("space"): "0",
                qn_w("color"): bottom_border.get("color", "94A3B8"),
            })

        p = ET.SubElement(tc, qn_w("p"))
        p_pr = ET.SubElement(p, qn_w("pPr"))
        ET.SubElement(p_pr, qn_w("spacing"), {
            qn_w("before"): "0",
            qn_w("after"): "0",
            qn_w("line"): "240",
            qn_w("lineRule"): "auto",
        })
        return tc, p

    def build_package(self) -> bytes:
        # A4 margins: 15mm (850 dxa) all around for maximum printable workspace
        sect_pr = ET.SubElement(self.body, qn_w("sectPr"))
        ET.SubElement(sect_pr, qn_w("pgSz"), {qn_w("w"): "11906", qn_w("h"): "16838"})  # A4
        ET.SubElement(sect_pr, qn_w("pgMar"), {
            qn_w("top"): "850",
            qn_w("right"): "850",
            qn_w("bottom"): "850",
            qn_w("left"): "850",
            qn_w("header"): "500",
            qn_w("footer"): "500",
        })

        content_types = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">\n'
            '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>\n'
            '  <Default Extension="xml" ContentType="application/xml"/>\n'
            '  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>\n'
            '  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>\n'
            '  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>\n'
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
            '  <Relationship Id="rIdSettings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>\n'
            '</Relationships>'
        )

        # Word Settings - compatibilityMode 15 disables 'Режим совместимости'
        settings_xml = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"\n'
            '            xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"\n'
            '            xmlns:o="urn:schemas-microsoft-com:office:office"\n'
            '            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">\n'
            '  <w:compat>\n'
            '    <w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/>\n'
            '    <w:compatSetting w:name="overrideTableStyleFontSizeAndJustification" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>\n'
            '    <w:compatSetting w:name="enableOpenTypeFeatures" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>\n'
            '    <w:compatSetting w:name="doNotFlipMirrorIndents" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>\n'
            '  </w:compat>\n'
            '</w:settings>'
        )

        # Word Styles with default noProof to suppress wavy underlines
        styles_xml = (
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">\n'
            '  <w:docDefaults>\n'
            '    <w:rPrDefault>\n'
            '      <w:rPr>\n'
            '        <w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>\n'
            '        <w:sz w:val="21"/>\n'
            '        <w:szCs w:val="21"/>\n'
            '        <w:noProof/>\n'
            '        <w:lang w:val="ru-RU"/>\n'
            '      </w:rPr>\n'
            '    </w:rPrDefault>\n'
            '    <w:pPrDefault>\n'
            '      <w:pPr>\n'
            '        <w:spacing w:line="240" w:lineRule="auto" w:before="0" w:after="40"/>\n'
            '      </w:pPr>\n'
            '    </w:pPrDefault>\n'
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

        buf = BytesIO()
        with ZipFile(buf, "w", compression=ZIP_DEFLATED) as zf:
            zf.writestr("[Content_Types].xml", content_types)
            zf.writestr("_rels/.rels", root_rels)
            zf.writestr("word/_rels/document.xml.rels", doc_rels)
            zf.writestr("word/document.xml", doc_xml)
            zf.writestr("word/settings.xml", settings_xml)
            zf.writestr("word/styles.xml", styles_xml)
            zf.writestr("docProps/core.xml", core_xml)
            zf.writestr("docProps/app.xml", app_xml)

        return buf.getvalue()


def render_variant_block(
    builder: ExamDocxBuilder,
    variant: dict,
    subject: str,
    work_type_title: str,
    grade_label: str,
    topic: str,
    is_compact_2up: bool = False,
):
    """
    Renders one variant block: Header Table, Student Line, Part A options table, Part B fields, and Answer Sheet.
    """
    var_num = variant.get("number", 1)
    var_title = variant.get("title", f"Вариант {var_num}")
    part_a = variant.get("part_a", [])
    part_b = variant.get("part_b", [])

    sp_scale = 0.7 if is_compact_2up else 1.0

    # 1. Header Table: [Logo ❖ EDUSFERA] [Subject • Topic] [Variant Box]
    hdr_tbl = builder.create_table([2400, 5600, 2200], total_width=10200)
    r_hdr = builder.add_table_row(hdr_tbl, min_height_pt=26 if is_compact_2up else 32)

    # Logo Column
    tc_logo, p_logo = builder.add_table_cell(r_hdr, 2400, v_align="center")
    builder.append_run(p_logo, "❖ EDUSFERA", bold=True, font_size_pt=13, color="0F172A")
    p_logo_sub = builder.add_paragraph(font_size_pt=7.5, italic=True, color="64748B", space_after_pt=0, parent=tc_logo)
    builder.append_run(p_logo_sub, "edusfera.by • Экзамены", italic=True, font_size_pt=7.5, color="64748B")

    # Center Subject & Topic Column
    tc_mid, p_mid = builder.add_table_cell(r_hdr, 5600, v_align="center")
    p_mid_pr = p_mid.find(qn_w("pPr"))
    ET.SubElement(p_mid_pr, qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_mid, f"{subject} • {work_type_title} ({grade_label})", bold=True, font_size_pt=10.5, color="0F172A")

    p_topic = builder.add_paragraph(font_size_pt=9.0, color="334155", align="center", space_before_pt=1, space_after_pt=0, parent=tc_mid)
    builder.append_run(p_topic, f"Тема: {topic}", font_size_pt=9.0, color="334155")

    # Right Variant Box Column (Bordered box)
    tc_right, p_right = builder.add_table_cell(
        r_hdr,
        2200,
        bg_color="F8FAFC",
        all_borders={
            "top": {"val": "single", "sz": 8, "color": "0F172A"},
            "left": {"val": "single", "sz": 8, "color": "0F172A"},
            "bottom": {"val": "single", "sz": 8, "color": "0F172A"},
            "right": {"val": "single", "sz": 8, "color": "0F172A"},
        },
        v_align="center",
    )
    p_right_pr = p_right.find(qn_w("pPr"))
    ET.SubElement(p_right_pr, qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_right, var_title, bold=True, font_size_pt=11.0, color="0F172A")

    # 2. Student Details Table (Clean bottom underlines)
    stud_tbl = builder.create_table([1000, 4700, 700, 1500, 600, 1700], total_width=10200)
    r_stud = builder.add_table_row(stud_tbl, min_height_pt=16)

    # 1. "Учащийся:"
    _, p_s1 = builder.add_table_cell(r_stud, 1000, v_align="center")
    builder.append_run(p_s1, "Учащийся:", font_size_pt=9.0, color="475569")

    # 2. Input line
    builder.add_table_cell(r_stud, 4700, bottom_border={"val": "single", "sz": 6, "color": "94A3B8"})

    # 3. "Класс:"
    _, p_s3 = builder.add_table_cell(r_stud, 700, v_align="center")
    p_s3_pr = p_s3.find(qn_w("pPr"))
    ET.SubElement(p_s3_pr, qn_w("jc"), {qn_w("val"): "right"})
    builder.append_run(p_s3, "Класс:", font_size_pt=9.0, color="475569")

    # 4. Input line for Class with grade prefill
    _, p_s4 = builder.add_table_cell(r_stud, 1500, bottom_border={"val": "single", "sz": 6, "color": "94A3B8"}, v_align="center")
    p_s4_pr = p_s4.find(qn_w("pPr"))
    ET.SubElement(p_s4_pr, qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_s4, f"{grade_label.split(' ')[0]}-___", font_size_pt=9.0, color="334155")

    # 5. "Дата:"
    _, p_s5 = builder.add_table_cell(r_stud, 600, v_align="center")
    p_s5_pr = p_s5.find(qn_w("pPr"))
    ET.SubElement(p_s5_pr, qn_w("jc"), {qn_w("val"): "right"})
    builder.append_run(p_s5, "Дата:", font_size_pt=9.0, color="475569")

    # 6. Input line for Date
    builder.add_table_cell(r_stud, 1700, bottom_border={"val": "single", "sz": 6, "color": "94A3B8"})

    # 3. Tasks
    # Part A
    if part_a:
        for t_idx, task in enumerate(part_a):
            t_num = task.get("number") or str(t_idx + 1)
            t_prompt = task.get("prompt") or task.get("condition") or ""

            p_t = builder.add_paragraph(
                space_before_pt=2.5 * sp_scale,
                space_after_pt=1.5 * sp_scale,
                line_spacing=1.1,
            )
            builder.append_run(p_t, f"{t_num}. ", bold=True, font_size_pt=10.0, color="0F172A")
            builder.append_text_with_math(p_t, t_prompt, font_size_pt=10.0, color="0F172A")

            options = task.get("options", [])
            if options:
                # 4-5 column options table
                num_opts = len(options)
                col_w = int(10200 / num_opts)
                widths = [col_w] * num_opts
                widths[-1] = 10200 - sum(widths[:-1])

                opt_tbl = builder.create_table(widths, total_width=10200)
                r_opt = builder.add_table_row(opt_tbl, min_height_pt=14)

                opt_letters = ["А", "Б", "В", "Г", "Д"]
                for o_idx, opt in enumerate(options):
                    let = opt.get("letter") or (opt_letters[o_idx] if o_idx < len(opt_letters) else str(o_idx + 1))
                    txt = str(opt.get("text", "")).strip()

                    _, p_o = builder.add_table_cell(
                        r_opt,
                        widths[o_idx],
                        bg_color="F8FAFC",
                        all_borders={
                            "top": {"val": "single", "sz": 4, "color": "E2E8F0"},
                            "left": {"val": "single", "sz": 4, "color": "E2E8F0"},
                            "bottom": {"val": "single", "sz": 4, "color": "E2E8F0"},
                            "right": {"val": "single", "sz": 4, "color": "E2E8F0"},
                        },
                        v_align="center",
                    )
                    builder.append_run(p_o, f" [{let}]  ", bold=True, font_size_pt=9.0, color="334155")
                    builder.append_text_with_math(p_o, txt, font_size_pt=9.0, color="0F172A")

    # Part B
    if part_b:
        for t_idx, task in enumerate(part_b):
            t_num = task.get("number") or str(len(part_a) + t_idx + 1)
            t_prompt = task.get("prompt") or task.get("condition") or ""

            p_t = builder.add_paragraph(
                space_before_pt=3.0 * sp_scale,
                space_after_pt=1.5 * sp_scale,
                line_spacing=1.1,
            )
            builder.append_run(p_t, f"{t_num}. ", bold=True, font_size_pt=10.0, color="0F172A")
            builder.append_text_with_math(p_t, t_prompt, font_size_pt=10.0, color="0F172A")

            # Part B student answer line
            ans_tbl = builder.create_table([1400, 3200, 5600], total_width=10200)
            r_ans = builder.add_table_row(ans_tbl, min_height_pt=15)

            _, p_a1 = builder.add_table_cell(r_ans, 1400, v_align="center")
            builder.append_run(p_a1, "Ответ ученика:", italic=True, font_size_pt=8.5, color="475569")

            builder.add_table_cell(r_ans, 3200, bottom_border={"val": "single", "sz": 6, "color": "64748B"})
            builder.add_table_cell(r_ans, 5600)  # Empty spacer

    # 4. Student Answer Sheet Table
    total_tasks_count = len(part_a) + len(part_b)
    # Cell widths: label ~1800, score ~2000, remaining divided among questions
    q_cell_w = max(900, int((10200 - 1800 - 2000) / max(1, total_tasks_count)))
    sheet_widths = [1800] + [q_cell_w] * total_tasks_count + [2000]
    sheet_widths[-1] = 10200 - sum(sheet_widths[:-1])

    sheet_tbl = builder.create_table(
        sheet_widths,
        total_width=10200,
        borders={
            "top": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "left": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "bottom": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "right": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "insideH": {"val": "single", "sz": 4, "color": "E2E8F0"},
            "insideV": {"val": "single", "sz": 4, "color": "E2E8F0"},
        },
    )
    r_sheet = builder.add_table_row(sheet_tbl, min_height_pt=16)

    # Label cell
    _, p_sh_lbl = builder.add_table_cell(r_sheet, sheet_widths[0], bg_color="F1F5F9", v_align="center")
    p_sh_lbl_pr = p_sh_lbl.find(qn_w("pPr"))
    ET.SubElement(p_sh_lbl_pr, qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_sh_lbl, f"Бланк ответов ({var_title}):", bold=True, font_size_pt=8.0, color="1E293B")

    # Question answer boxes
    for q_i in range(1, total_tasks_count + 1):
        _, p_q = builder.add_table_cell(r_sheet, sheet_widths[q_i], bg_color="FFFFFF", v_align="center")
        p_q_pr = p_q.find(qn_w("pPr"))
        ET.SubElement(p_q_pr, qn_w("jc"), {qn_w("val"): "center"})
        builder.append_run(p_q, f"№{q_i}: [      ]", font_size_pt=8.0, color="475569")

    # Score cell
    _, p_sc = builder.add_table_cell(r_sheet, sheet_widths[-1], bg_color="F8FAFC", v_align="center")
    p_sc_pr = p_sc.find(qn_w("pPr"))
    ET.SubElement(p_sc_pr, qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_sc, "Отметка: [    / 10 ]", bold=True, font_size_pt=8.0, color="1E293B")


def render_teacher_keys_page(
    builder: ExamDocxBuilder,
    variants: list[dict],
    master_table: list[dict],
    grading_scale: list[dict],
    subject: str,
    title: str,
    topic: str,
):
    """
    Renders Teacher Answer Key, Solutions, and 10-point Grading Scale on a separate page.
    """
    builder.add_page_break()

    # Teacher Header
    p_hdr = builder.add_paragraph(align="center", space_before_pt=4, space_after_pt=2)
    builder.append_run(p_hdr, "КЛЮЧИ И ОТВЕТЫ ДЛЯ ПРЕПОДАВАТЕЛЯ (НЕ ВЫДАВАТЬ УЧЕНИКАМ)", bold=True, font_size_pt=12.0, color="DC2626")

    p_sub = builder.add_paragraph(align="center", space_after_pt=6)
    builder.append_run(p_sub, f"Предмет: {subject} • {title} • Стандарты экзаменов 2026", italic=True, font_size_pt=9.0, color="475569")

    # Master Table
    num_vars = len(variants)
    col_var_w = 1400
    w_num = 700
    w_score = 900
    w_expl = 10200 - w_num - (col_var_w * num_vars) - w_score

    widths = [w_num] + [col_var_w] * num_vars + [w_score, w_expl]
    m_tbl = builder.create_table(
        widths,
        total_width=10200,
        borders={
            "top": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "left": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "bottom": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "right": {"val": "single", "sz": 6, "color": "CBD5E1"},
            "insideH": {"val": "single", "sz": 4, "color": "E2E8F0"},
            "insideV": {"val": "single", "sz": 4, "color": "E2E8F0"},
        },
    )

    # Header Row
    r_hdr = builder.add_table_row(m_tbl, min_height_pt=18)

    # №
    _, p_h1 = builder.add_table_cell(r_hdr, w_num, bg_color="1E293B", v_align="center")
    ET.SubElement(p_h1.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_h1, "№", bold=True, font_size_pt=9.0, color="FFFFFF")

    # Variants Headers
    for v_idx, var in enumerate(variants):
        var_num = var.get("number", v_idx + 1)
        _, p_hv = builder.add_table_cell(r_hdr, col_var_w, bg_color="1E293B", v_align="center")
        ET.SubElement(p_hv.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
        builder.append_run(p_hv, f"В-{var_num}", bold=True, font_size_pt=9.0, color="FFFFFF")

    # Score Header
    _, p_hs = builder.add_table_cell(r_hdr, w_score, bg_color="1E293B", v_align="center")
    ET.SubElement(p_hs.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_hs, "Балл", bold=True, font_size_pt=9.0, color="FFFFFF")

    # Explanation Header
    _, p_he = builder.add_table_cell(r_hdr, w_expl, bg_color="1E293B", v_align="center")
    ET.SubElement(p_he.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
    builder.append_run(p_he, "Обоснование / Разбор решения", bold=True, font_size_pt=9.0, color="FFFFFF")

    # Data Rows
    max_tasks = max(len(v.get("part_a", []) + v.get("part_b", [])) for v in variants) if variants else 0

    for t_idx in range(max_tasks):
        bg = "FFFFFF" if t_idx % 2 == 0 else "F8FAFC"
        r_data = builder.add_table_row(m_tbl, min_height_pt=18)

        # №
        _, p_num = builder.add_table_cell(r_data, w_num, bg_color=bg, v_align="center")
        ET.SubElement(p_num.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
        builder.append_run(p_num, str(t_idx + 1), bold=True, font_size_pt=9.0, color="0F172A")

        expl_text = ""
        # Variant answers
        for v in variants:
            all_v_tasks = v.get("part_a", []) + v.get("part_b", [])
            t_obj = all_v_tasks[t_idx] if t_idx < len(all_v_tasks) else None
            ans = t_obj.get("correct", "—") if t_obj else "—"
            if t_obj and not expl_text and t_obj.get("explanation"):
                expl_text = t_obj.get("explanation")

            _, p_ans = builder.add_table_cell(r_data, col_var_w, bg_color=bg, v_align="center")
            ET.SubElement(p_ans.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
            builder.append_run(p_ans, str(ans), bold=True, font_size_pt=9.5, color="16A34A")

        # Score
        _, p_sc = builder.add_table_cell(r_data, w_score, bg_color=bg, v_align="center")
        ET.SubElement(p_sc.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
        builder.append_run(p_sc, "1 б.", font_size_pt=8.5, color="475569")

        # Explanation
        _, p_ex = builder.add_table_cell(r_data, w_expl, bg_color=bg, v_align="center")
        if expl_text:
            builder.append_text_with_math(p_ex, expl_text, font_size_pt=8.5, color="334155")
        else:
            builder.append_run(p_ex, "Сверено по программе курса.", italic=True, font_size_pt=8.0, color="94A3B8")

    # Grading Scale Section
    p_sc_hdr = builder.add_paragraph(space_before_pt=8, space_after_pt=2)
    builder.append_run(p_sc_hdr, "Шкала перевода первичных баллов в 10-балльную отметку:", bold=True, font_size_pt=9.5, color="0F172A")

    scale_tbl = builder.create_table([1020] * 10, total_width=10200, borders={
        "top": {"val": "single", "sz": 4, "color": "CBD5E1"},
        "left": {"val": "single", "sz": 4, "color": "CBD5E1"},
        "bottom": {"val": "single", "sz": 4, "color": "CBD5E1"},
        "right": {"val": "single", "sz": 4, "color": "CBD5E1"},
        "insideH": {"val": "single", "sz": 4, "color": "E2E8F0"},
        "insideV": {"val": "single", "sz": 4, "color": "E2E8F0"},
    })

    # Marks 1 to 10
    r_mark = builder.add_table_row(scale_tbl, min_height_pt=14)
    for mark in range(1, 11):
        _, p_m = builder.add_table_cell(r_mark, 1020, bg_color="F1F5F9", v_align="center")
        ET.SubElement(p_m.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
        builder.append_run(p_m, f"{mark}", bold=True, font_size_pt=8.5, color="1E293B")

    # Percentage / range
    r_pts = builder.add_table_row(scale_tbl, min_height_pt=14)
    ranges = ["1-10%", "11-20%", "21-30%", "31-40%", "41-50%", "51-65%", "66-75%", "76-85%", "86-94%", "95-100%"]
    for rng in ranges:
        _, p_r = builder.add_table_cell(r_pts, 1020, bg_color="FFFFFF", v_align="center")
        ET.SubElement(p_r.find(qn_w("pPr")), qn_w("jc"), {qn_w("val"): "center"})
        builder.append_run(p_r, rng, font_size_pt=7.5, color="475569")


def convert_exam_json_to_docx(json_data: dict, out_path: Path):
    meta = json_data.get("meta", {})
    # Support both flat keys and meta dictionary
    title = meta.get("title") or json_data.get("title") or "Проверочная работа"
    subject = meta.get("subject") or json_data.get("subject") or "Математика"
    topic = meta.get("topic") or json_data.get("topic") or "Стандарты экзаменов 2026"
    grade_label = meta.get("grade_label") or json_data.get("grade_label") or "7 класс"
    work_type_title = meta.get("work_type_title") or json_data.get("work_type_title") or "Самостоятельная работа"

    variants = json_data.get("variants", [])
    master_table = json_data.get("master_table", [])
    grading_scale = json_data.get("grading_scale", [])

    builder = ExamDocxBuilder(title=title, subject=subject)

    # Check if 2-up desk-paired format is applicable (<= 5 questions per variant and exactly 2 variants)
    is_2up = len(variants) == 2 and all(len(v.get("part_a", []) + v.get("part_b", [])) <= 5 for v in variants)

    if is_2up:
        # Variant 1 on top half of page 1
        render_variant_block(
            builder,
            variants[0],
            subject,
            work_type_title,
            grade_label,
            topic,
            is_compact_2up=True,
        )

        # Scissors cut divider in the middle of page 1
        builder.add_horizontal_divider("Линия отреза (раздать по вариантам)")

        # Variant 2 on bottom half of page 1
        render_variant_block(
            builder,
            variants[1],
            subject,
            work_type_title,
            grade_label,
            topic,
            is_compact_2up=True,
        )
    else:
        # Standard layout: each variant gets its own page or flow
        for v_idx, variant in enumerate(variants):
            render_variant_block(
                builder,
                variant,
                subject,
                work_type_title,
                grade_label,
                topic,
                is_compact_2up=False,
            )
            if v_idx < len(variants) - 1:
                builder.add_page_break()

    # Teacher Keys on a dedicated page
    render_teacher_keys_page(
        builder,
        variants,
        master_table,
        grading_scale,
        subject,
        title,
        topic,
    )

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
