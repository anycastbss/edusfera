from __future__ import annotations

import base64
import json
import math
import os
import re
import tempfile
import uuid
from pathlib import Path
from typing import Any
from xml.sax.saxutils import escape

import qrcode
import matplotlib

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Flowable, Frame, Image, KeepTogether, PageBreak, NextPageTemplate,
    PageTemplate, Paragraph, Spacer, Table, TableStyle
)

ROOT = Path(__file__).resolve().parent
PAGE_W, PAGE_H = A4
LEFT = 14 * mm
RIGHT = 14 * mm
CONTENT_W = PAGE_W - LEFT - RIGHT


def _find_font(candidates: list[str]) -> str:
    for candidate in candidates:
        if Path(candidate).exists():
            return candidate
    raise FileNotFoundError("Не найден шрифт DejaVu Sans. Установите пакет fonts-dejavu-core.")


MPL_FONTS = Path(matplotlib.get_data_path()) / "fonts" / "ttf"
FONT_REGULAR = _find_font([
    "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    "/usr/local/share/fonts/DejaVuSans.ttf",
    "/System/Library/Fonts/Supplemental/Arial.ttf",
    str(MPL_FONTS / "DejaVuSans.ttf"),
])
FONT_BOLD = _find_font([
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
    "/usr/local/share/fonts/DejaVuSans-Bold.ttf",
    "/System/Library/Fonts/Supplemental/Arial Bold.ttf",
    str(MPL_FONTS / "DejaVuSans-Bold.ttf"),
])
FONT_ITALIC = _find_font([
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Oblique.ttf",
    "/usr/local/share/fonts/DejaVuSans-Oblique.ttf",
    "/System/Library/Fonts/Supplemental/Arial Italic.ttf",
    str(MPL_FONTS / "DejaVuSans-Oblique.ttf"),
    str(MPL_FONTS / "DejaVuSans.ttf"),
])

if "EDU" not in pdfmetrics.getRegisteredFontNames():
    pdfmetrics.registerFont(TTFont("EDU", FONT_REGULAR))
    pdfmetrics.registerFont(TTFont("EDU-Bold", FONT_BOLD))
    pdfmetrics.registerFont(TTFont("EDU-Italic", FONT_ITALIC))


STYLES = {
    "body": ParagraphStyle("body", fontName="EDU", fontSize=9.2, leading=13.2,
                            textColor=colors.black, spaceAfter=0, allowWidows=0, allowOrphans=0),
    "body_small": ParagraphStyle("body_small", fontName="EDU", fontSize=8, leading=10.5,
                                  textColor=colors.black),
    "question": ParagraphStyle("question", fontName="EDU", fontSize=9.5, leading=13.5,
                                textColor=colors.black, spaceAfter=0),
    "answer": ParagraphStyle("answer", fontName="EDU", fontSize=8.7, leading=12,
                             textColor=colors.black),
    "key_title": ParagraphStyle("key_title", fontName="EDU-Bold", fontSize=16, leading=20,
                                 textColor=colors.black, spaceAfter=8),
    "section": ParagraphStyle("section", fontName="EDU-Bold", fontSize=9.4, leading=12,
                               textColor=colors.black),
}


def _safe_formula_markup(text: str, asset_dir: Path) -> str:
    """Convert $math$ spans to tiny local PNGs for crisp inline maths in ReportLab."""
    try:
        from matplotlib.mathtext import math_to_image
        from matplotlib.font_manager import FontProperties
    except Exception:
        return escape(text).replace("\n", "<br/>")

    pieces = re.split(r"(\$[^$]+\$)", str(text))
    output: list[str] = []
    counter = 0
    for piece in pieces:
        if len(piece) >= 2 and piece.startswith("$") and piece.endswith("$"):
            expr = piece[1:-1].strip()
            counter += 1
            file_path = asset_dir / f"math_{counter}_{uuid.uuid4().hex[:6]}.png"
            try:
                math_to_image(f"${expr}$", str(file_path), prop=FontProperties(size=11), dpi=180,
                              format="png", color="black")
                from PIL import Image as PILImage
                with PILImage.open(file_path) as im:
                    w_px, h_px = im.size
                # Inline formula height is controlled by its rendered pixel size.
                height = max(9, min(25, h_px * 0.34))
                width = max(10, w_px * (height / h_px))
                output.append(f'<img src="{file_path.as_posix()}" width="{width:.1f}" height="{height:.1f}" valign="middle"/>')
            except Exception:
                # Keep the original formula visible even if a particular MathText command is unsupported.
                output.append(escape(piece))
        else:
            output.append(escape(piece).replace("\n", "<br/>"))
    return "".join(output)


class QuestionHeader(Flowable):
    def __init__(self, number: str, prompt: Paragraph, width: float):
        super().__init__()
        self.number = number
        self.prompt = prompt
        self.width = width
        self.label_w = 39
        self.gap = 10

    def wrap(self, availWidth, availHeight):
        self.width = min(self.width, availWidth)
        text_w = self.width - self.label_w - self.gap
        _, text_h = self.prompt.wrap(text_w, availHeight)
        self.height = max(24, text_h) + 2
        return self.width, self.height

    def draw(self):
        y = (self.height - 22) / 2
        self.canv.setLineWidth(0.8)
        self.canv.roundRect(0, y, self.label_w - 2, 22, 4, stroke=1, fill=0)
        self.canv.setFont("EDU-Bold", 11)
        self.canv.drawCentredString((self.label_w - 2) / 2, y + 6.1, self.number)
        self.prompt.drawOn(self.canv, self.label_w + self.gap, (self.height - self.prompt.height) / 2)


class ChoicesFlowable(Flowable):
    def __init__(self, options: list[dict[str, Any]], width: float, asset_dir: Path, font_size: float = 8.8):
        super().__init__()
        self.options = options
        self.width = width
        self.asset_dir = asset_dir
        self.font_size = font_size
        self.cells: list[tuple[Paragraph, float, float]] = []

    def wrap(self, availWidth, availHeight):
        self.width = min(self.width, availWidth)
        n = max(1, len(self.options))
        col_w = self.width / n
        self.cells = []
        max_h = 18
        for opt in self.options:
            text = str(opt.get("text", ""))
            para = Paragraph(_safe_formula_markup(text, self.asset_dir), ParagraphStyle(
                f"choice_{len(self.cells)}", fontName="EDU", fontSize=self.font_size,
                leading=self.font_size + 2.1, textColor=colors.black))
            _, ph = para.wrap(col_w - 24, availHeight)
            cell_h = max(18, ph + 1)
            max_h = max(max_h, cell_h)
            self.cells.append((para, col_w, cell_h))
        self.height = max_h + 7
        return self.width, self.height

    def draw(self):
        n = max(1, len(self.options))
        col_w = self.width / n
        y_base = 3
        for idx, (para, _, cell_h) in enumerate(self.cells):
            x = idx * col_w + 2
            tag_y = y_base + (self.height - 7 - 17) / 2
            self.canv.setLineWidth(0.65)
            self.canv.roundRect(x, tag_y, 17, 17, 3, stroke=1, fill=0)
            self.canv.setFont("EDU-Bold", 8.8)
            self.canv.drawCentredString(x + 8.5, tag_y + 5.2, chr(65 + idx))
            para_w = col_w - 25
            _, ph = para.wrap(para_w, self.height)
            para.drawOn(self.canv, x + 22, y_base + (self.height - 7 - ph) / 2)


class PartBAnswerFlowable(Flowable):
    def __init__(self, width: float):
        super().__init__()
        self.width = width
        self.height = 24

    def wrap(self, availWidth, availHeight):
        self.width = min(self.width, availWidth)
        return self.width, self.height

    def draw(self):
        c = self.canv
        c.saveState()
        c.setFont("EDU-Bold", 8.8)
        c.setFillColor(colors.black)
        c.drawString(0, 6, "Ответ:")

        # Sign cell
        c.setFillColor(colors.HexColor("#f0f0f0"))
        c.setLineWidth(0.65)
        c.roundRect(40, 2, 17, 18, 2, stroke=1, fill=1)
        c.setFillColor(colors.HexColor("#555555"))
        c.setFont("EDU-Bold", 8.5)
        c.drawCentredString(40 + 8.5, 6, "±")

        # 4 digit cells
        c.setFillColor(colors.white)
        for i in range(4):
            x = 40 + 20 + i * 19
            c.roundRect(x, 2, 17, 18, 2, stroke=1, fill=0)

        c.setFillColor(colors.HexColor("#777777"))
        c.setFont("EDU", 7.2)
        c.drawString(40 + 20 + 4 * 19 + 8, 6, "(в бланк ответов запишите полученное целое число)")
        c.restoreState()


class SectionBanner(Flowable):
    def __init__(self, text: str, width: float):
        super().__init__()
        self.text, self.width, self.height = text, width, 25

    def wrap(self, availWidth, availHeight):
        self.width = min(self.width, availWidth)
        return self.width, self.height

    def draw(self):
        self.canv.setFillColor(colors.HexColor("#eeeeee"))
        self.canv.roundRect(0, 0, self.width, self.height, 5, stroke=0, fill=1)
        self.canv.setFillColor(colors.black)
        self.canv.setFont("EDU-Bold", 9.2)
        self.canv.drawString(10, 8.2, self.text)


class ExamDocTemplate(BaseDocTemplate):
    def __init__(self, filename: str, meta: dict[str, Any], doc_kind: str = "student", **kwargs):
        self.meta = meta
        self.doc_kind = doc_kind
        super().__init__(filename, pagesize=A4, leftMargin=LEFT, rightMargin=RIGHT,
                         topMargin=18 * mm, bottomMargin=15 * mm, **kwargs)
        first_height = 214 * mm if doc_kind == "key" else 195 * mm
        first_frame = Frame(LEFT, 15 * mm, CONTENT_W, first_height,
                            leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0, id="first")
        later_frame = Frame(LEFT, 15 * mm, CONTENT_W, PAGE_H - 35 * mm - 15 * mm,
                            leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0, id="later")
        self.addPageTemplates([
            PageTemplate(id="First", frames=[first_frame], onPage=self._draw_first_header),
            PageTemplate(id="Later", frames=[later_frame], onPage=self._draw_later_header),
        ])

    def _draw_logo(self, canvas, x: float, y: float, compact: bool = False):
        size = 29 if compact else 33
        canvas.saveState()
        canvas.setFillColor(colors.black)
        canvas.roundRect(x, y, size, size, 7, stroke=0, fill=1)
        canvas.setStrokeColor(colors.white)
        canvas.setLineWidth(2.3)
        cx, cy = x + size / 2, y + size / 2
        d = size * 0.31
        canvas.line(cx, cy + d, cx + d, cy)
        canvas.line(cx + d, cy, cx, cy - d)
        canvas.line(cx, cy - d, cx - d, cy)
        canvas.line(cx - d, cy, cx, cy + d)
        d2 = size * 0.16
        canvas.setLineWidth(1.7)
        canvas.line(cx, cy + d2, cx + d2, cy)
        canvas.line(cx + d2, cy, cx, cy - d2)
        canvas.line(cx, cy - d2, cx - d2, cy)
        canvas.line(cx - d2, cy, cx, cy + d2)
        canvas.setFillColor(colors.black)
        canvas.setFont("EDU-Bold", 15 if compact else 18)
        canvas.drawString(x + size + 8, y + (8 if compact else 9), "EDUSFERA")
        canvas.restoreState()

    def _qr(self):
        url = self.meta.get("website_url") or "https://edusfera.by"
        qr = qrcode.QRCode(box_size=4, border=1)
        qr.add_data(url)
        qr.make(fit=True)
        return qr.make_image(fill_color="black", back_color="white").convert("RGB")

    def _draw_first_header(self, canvas, doc):
        canvas.saveState()
        self._draw_logo(canvas, LEFT, PAGE_H - 51)
        canvas.setFont("EDU-Italic", 10.5)
        canvas.drawCentredString(PAGE_W / 2 + 4, PAGE_H - 31, "Не зубри. Знай.")
        canvas.setLineWidth(0.9)
        canvas.line(PAGE_W / 2 - 42, PAGE_H - 39, PAGE_W / 2 + 42, PAGE_H - 39)

        qr_img = self._qr()
        from reportlab.lib.utils import ImageReader
        canvas.drawImage(ImageReader(qr_img), PAGE_W - RIGHT - 44, PAGE_H - 56, width=42, height=42,
                         preserveAspectRatio=True, mask="auto")
        canvas.setFont("EDU", 6.7)
        canvas.drawRightString(PAGE_W - RIGHT - 49, PAGE_H - 27, "Больше тестов")
        canvas.drawRightString(PAGE_W - RIGHT - 49, PAGE_H - 36, "на edusfera.by")

        subject = str(self.meta.get("subject", "Предмет"))
        title = str(self.meta.get("title", "Пробный тест"))
        canvas.setFont("EDU-Bold", 22)
        canvas.drawString(LEFT, PAGE_H - 91, subject)
        canvas.setFont("EDU-Bold", 12.5)
        canvas.drawString(LEFT, PAGE_H - 112, title[:70])

        card_x, card_y, card_w, card_h = PAGE_W - RIGHT - 190, PAGE_H - 131, 190, 57
        canvas.setFillColor(colors.HexColor("#eeeeee"))
        canvas.roundRect(card_x, card_y, card_w, card_h, 7, stroke=0, fill=1)
        canvas.setFillColor(colors.black)
        canvas.setFont("EDU", 8.3)
        duration = str(self.meta.get("duration", "90 минут"))
        max_score = str(self.meta.get("max_score", "100"))
        count = len(self.meta.get("questions", []))
        canvas.drawString(card_x + 12, card_y + 37, f"Время выполнения: {duration}")
        canvas.drawString(card_x + 12, card_y + 24, f"Количество заданий: {count}")
        canvas.drawString(card_x + 12, card_y + 11, f"Максимальный балл: {max_score}")
        canvas.setStrokeColor(colors.black)
        canvas.setLineWidth(0.7)
        canvas.line(LEFT, PAGE_H - 143, PAGE_W - RIGHT, PAGE_H - 143)

        if self.doc_kind == "key":
            canvas.setFillColor(colors.HexColor("#e9e9e9"))
            canvas.roundRect(LEFT, PAGE_H - 180, CONTENT_W, 25, 5, stroke=0, fill=1)
            canvas.setFillColor(colors.black)
            canvas.setFont("EDU-Bold", 9.1)
            canvas.drawString(LEFT + 10, PAGE_H - 172, "КЛЮЧ ПРЕПОДАВАТЕЛЯ  •  НЕ ВЫДАВАТЬ УЧЕНИКУ")
            canvas.restoreState()
            return

        instruction = str(self.meta.get("instructions") or
                          "В заданиях с выбором ответа отметьте один правильный вариант. Ответы перенесите в бланк в конце теста.")
        box_y, box_h = PAGE_H - 204, 49
        canvas.roundRect(LEFT, box_y, CONTENT_W, box_h, 6, stroke=1, fill=0)
        canvas.setFont("EDU-Bold", 10)
        canvas.drawString(LEFT + 12, box_y + 32, "Инструкция")
        p = Paragraph(escape(instruction).replace("\n", "<br/>"), ParagraphStyle(
            "instruction", fontName="EDU", fontSize=8.1, leading=10.3))
        _, ph = p.wrap(CONTENT_W - 24, 24)
        p.drawOn(canvas, LEFT + 12, box_y + 7)

        canvas.setFillColor(colors.HexColor("#e9e9e9"))
        canvas.roundRect(LEFT, PAGE_H - 238, CONTENT_W, 25, 5, stroke=0, fill=1)
        canvas.setFillColor(colors.black)
        canvas.setFont("EDU-Bold", 9.1)
        section_name = str(self.meta.get("section_title", "Часть A. Задания с выбором одного правильного ответа"))
        canvas.drawString(LEFT + 10, PAGE_H - 230, section_name[:100])
        canvas.restoreState()

    def _draw_later_header(self, canvas, doc):
        canvas.saveState()
        self._draw_logo(canvas, LEFT, PAGE_H - 42, compact=True)
        canvas.setFont("EDU", 7.5)
        canvas.drawRightString(PAGE_W - RIGHT, PAGE_H - 27,
                               f"{self.meta.get('subject', '')}  |  {self.meta.get('title', '')[:40]}")
        canvas.setLineWidth(0.55)
        canvas.line(LEFT, PAGE_H - 49, PAGE_W - RIGHT, PAGE_H - 49)
        canvas.restoreState()

    def afterPage(self):
        c = self.canv
        c.saveState()
        c.setFont("EDU", 7)
        c.setFillColor(colors.HexColor("#555555"))
        c.drawCentredString(PAGE_W / 2, 7 * mm, f"EDUSFERA  •  {self.page}")
        c.restoreState()


def _question_flowables(q: dict[str, Any], asset_dir: Path, width: float) -> list[Flowable]:
    number = str(q.get("number") or q.get("id") or "")
    prompt_markup = _safe_formula_markup(str(q.get("prompt", "")), asset_dir)
    prompt = Paragraph(prompt_markup, STYLES["question"])
    parts: list[Flowable] = [QuestionHeader(number, prompt, width), Spacer(1, 4)]

    diagram_path = q.get("diagram_path")
    if diagram_path and not Path(diagram_path).is_absolute():
        diagram_path = str((ROOT / diagram_path).resolve())
    if diagram_path and Path(diagram_path).exists():
        img = Image(str(diagram_path))
        max_w = min(width * 0.62, 245)
        max_h = float(q.get("diagram_height", 105))
        scale = min(max_w / img.imageWidth, max_h / img.imageHeight, 1.0)
        img.drawWidth = img.imageWidth * scale
        img.drawHeight = img.imageHeight * scale
        img.hAlign = "CENTER"
        parts.extend([img, Spacer(1, 3)])

    options = q.get("options") or []
    if options:
        parts.append(ChoicesFlowable(options, width, asset_dir))
    else:
        parts.append(PartBAnswerFlowable(width))
    parts.extend([Spacer(1, 2)])
    # A restrained horizontal divider keeps the sheet structured without wasting toner.
    parts.append(Table([['']], colWidths=[width], rowHeights=[0.6], style=TableStyle([
        ("LINEBELOW", (0, 0), (-1, -1), 0.55, colors.HexColor("#777777")),
    ])))
    parts.append(Spacer(1, 7))
    return parts


def _answer_sheet(story: list[Flowable], questions: list[dict[str, Any]], width: float):
    story.append(PageBreak())
    story.append(Paragraph("Бланк ответов", STYLES["key_title"]))
    story.append(Paragraph("Заполните ячейки выбранными буквами ответов. Пишите разборчиво.", STYLES["body"]))
    story.append(Spacer(1, 12))
    chunk_size = 15
    for start in range(0, len(questions), chunk_size):
        subset = questions[start:start + chunk_size]
        ids = [str(q.get("number") or q.get("id") or f"A{start+i+1}") for i, q in enumerate(subset)]
        col0 = 58
        col = (width - col0) / max(1, len(ids))
        data = [["№ задания"] + ids, ["Ответ"] + ["" for _ in ids]]
        t = Table(data, colWidths=[col0] + [col] * len(ids), rowHeights=[25, 31])
        t.setStyle(TableStyle([
            ("FONTNAME", (0, 0), (-1, -1), "EDU"),
            ("FONTNAME", (0, 0), (0, -1), "EDU-Bold"),
            ("FONTSIZE", (0, 0), (-1, -1), 6.8),
            ("ALIGN", (0, 0), (-1, -1), "CENTER"),
            ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
            ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#ededed")),
            ("GRID", (0, 0), (-1, -1), 0.65, colors.black),
            ("LEFTPADDING", (0, 0), (-1, -1), 2),
            ("RIGHTPADDING", (0, 0), (-1, -1), 2),
        ]))
        story.append(t)
        story.append(Spacer(1, 10))
    story.append(Spacer(1, 13))
    story.append(Paragraph("ФИО: ________________________________________________________________", STYLES["body"]))
    story.append(Spacer(1, 8))
    story.append(Paragraph("Дата: ____________________    Группа: ____________________", STYLES["body"]))


def _key_story(data: dict[str, Any], asset_dir: Path) -> list[Flowable]:
    story: list[Flowable] = [NextPageTemplate("Later")]
    story.append(Paragraph("Ключ ответов и пояснения", STYLES["key_title"]))
    story.append(Paragraph(f"{escape(str(data.get('subject', 'Предмет')))} — {escape(str(data.get('title', 'Тест')))}", STYLES["body"]))
    story.append(Spacer(1, 12))
    rows = [["Задание", "Ответ", "Правильный вариант"]]
    for q in data["questions"]:
        ans = str(q.get("correct", "A")).upper()
        opts = q.get("options", [])
        if opts:
            text = next((str(o.get("text", "")) for i, o in enumerate(opts) if chr(65 + i) == ans), "")
        else:
            text = str(q.get("correct", ""))
        rows.append([str(q.get("number") or q.get("id")), ans if opts else "—", Paragraph(_safe_formula_markup(text, asset_dir), STYLES["body_small"])])
    table = Table(rows, colWidths=[72, 52, CONTENT_W - 124], repeatRows=1)
    table.setStyle(TableStyle([
        ("FONTNAME", (0, 0), (-1, 0), "EDU-Bold"),
        ("FONTNAME", (0, 1), (-1, -1), "EDU"),
        ("FONTSIZE", (0, 0), (-1, -1), 8),
        ("LEADING", (0, 0), (-1, -1), 10),
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#e9e9e9")),
        ("GRID", (0, 0), (-1, -1), 0.5, colors.HexColor("#666666")),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
    ]))
    story.append(table)
    story.append(Spacer(1, 14))
    for q in data["questions"]:
        number = escape(str(q.get("number") or q.get("id")))
        ans = str(q.get("correct", "A")).upper()
        options = q.get("options", [])
        answer_text = next((str(o.get("text", "")) for i, o in enumerate(options) if chr(65+i) == ans), "")
        heading = Paragraph(f"<b>{number}. Ответ: {ans}</b> — {_safe_formula_markup(answer_text, asset_dir)}", STYLES["answer"])
        explanation = str(q.get("explanation", "")).strip()
        bits: list[Flowable] = [heading]
        if explanation:
            bits.append(Paragraph(_safe_formula_markup(explanation, asset_dir), STYLES["body_small"]))
        bits.append(Spacer(1, 7))
        story.append(KeepTogether(bits))
    return story


def validate_data(data: dict[str, Any]) -> dict[str, Any]:
    if not isinstance(data, dict):
        raise ValueError("Ожидается объект с настройками теста и списком заданий.")
    questions = data.get("questions")
    if not isinstance(questions, list) or not questions:
        raise ValueError("Добавьте хотя бы одно задание.")
    normalized = dict(data)
    normalized.setdefault("subject", "Математика")
    normalized.setdefault("title", "Пробный тест в формате ЦТ")
    normalized.setdefault("duration", "90 минут")
    normalized.setdefault("max_score", "100")
    normalized.setdefault("website_url", "https://edusfera.by")
    normalized.setdefault("instructions", "В заданиях с выбором ответа отметьте один правильный вариант. Ответы перенесите в бланк в конце теста.")
    out_questions = []
    seen = set()
    for i, raw in enumerate(questions, 1):
        if not isinstance(raw, dict):
            raise ValueError(f"Задание {i}: неверный формат.")
        q = dict(raw)
        q.setdefault("number", f"A{i}")
        if q["number"] in seen:
            raise ValueError(f"Повторяется номер задания {q['number']}.")
        seen.add(q["number"])
        if not str(q.get("prompt", "")).strip():
            raise ValueError(f"{q['number']}: заполните условие задания.")
        opts = q.get("options") or []
        if opts:
            if len(opts) not in (4, 5):
                raise ValueError(f"{q['number']}: для формата с выбором ответа добавьте 4 или 5 вариантов ответа.")
            if any(not str(o.get("text", "")).strip() for o in opts):
                raise ValueError(f"{q['number']}: заполните текст каждого варианта ответа.")
            correct = str(q.get("correct", "A")).upper()
            if correct not in [chr(65+j) for j in range(len(opts))]:
                raise ValueError(f"{q['number']}: правильный ответ должен соответствовать одному из вариантов.")
            q["correct"] = correct
        else:
            q["correct"] = str(q.get("correct", "")).strip()
        out_questions.append(q)
    normalized["questions"] = out_questions
    return normalized


def generate_pdfs(data: dict[str, Any], out_dir: str | Path) -> tuple[Path, Path]:
    data = validate_data(data)
    out_dir = Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    assets = out_dir / "assets"
    assets.mkdir(parents=True, exist_ok=True)

    # Decode optional data-URL images from the browser editor.
    for i, q in enumerate(data["questions"], 1):
        if q.get("diagram_data"):
            raw = str(q.pop("diagram_data"))
            if "," in raw:
                raw = raw.split(",", 1)[1]
            try:
                decoded = base64.b64decode(raw, validate=True)
                target = assets / f"diagram_{i}.png"
                target.write_bytes(decoded)
                q["diagram_path"] = str(target)
            except Exception as exc:
                raise ValueError(f"{q['number']}: не удалось прочитать изображение схемы.") from exc

    student_pdf = out_dir / "edusfera_test_student.pdf"
    key_pdf = out_dir / "edusfera_test_teacher_key.pdf"

    student_doc = ExamDocTemplate(str(student_pdf), data, doc_kind="student")
    story: list[Flowable] = [NextPageTemplate("Later"), Spacer(1, 7)]
    for q in data["questions"]:
        blocks = _question_flowables(q, assets, CONTENT_W)
        # Keep each regular-sized task together so its prompt and choices are not split over pages.
        story.append(KeepTogether(blocks))
    if data.get("include_answer_sheet", True):
        _answer_sheet(story, data["questions"], CONTENT_W)
    student_doc.build(story)

    key_doc = ExamDocTemplate(str(key_pdf), data, doc_kind="key")
    key_doc.build(_key_story(data, assets))
    return student_pdf, key_pdf


def make_graph_diagram(path: Path):
    import matplotlib
    matplotlib.use("Agg")
    import matplotlib.pyplot as plt
    import numpy as np
    fig, ax = plt.subplots(figsize=(3.2, 1.9), dpi=180)
    x = np.linspace(-2.3, 3.2, 300)
    y = x*x - x - 2
    ax.plot(x, y, color="black", linewidth=1.5)
    ax.axhline(0, color="black", linewidth=0.8)
    ax.axvline(0, color="black", linewidth=0.8)
    ax.set_xlim(-2.3, 3.1); ax.set_ylim(-3.0, 4.5)
    ax.set_xticks([-2, -1, 0, 1, 2, 3]); ax.set_yticks([-2, 0, 2, 4])
    ax.grid(True, color="#c8c8c8", linewidth=0.5)
    ax.tick_params(labelsize=7, length=2)
    ax.spines['top'].set_visible(False); ax.spines['right'].set_visible(False)
    ax.spines['left'].set_position(('data', 0)); ax.spines['bottom'].set_position(('data', 0))
    fig.tight_layout(pad=0.35)
    fig.savefig(path, dpi=180, bbox_inches="tight", facecolor="white")
    plt.close(fig)


def make_triangle_diagram(path: Path):
    import matplotlib
    matplotlib.use("Agg")
    import matplotlib.pyplot as plt
    from matplotlib.patches import Polygon
    fig, ax = plt.subplots(figsize=(2.5, 1.55), dpi=180)
    A=(2.5,0); B=(0,2.4); C=(0,0)
    ax.add_patch(Polygon([A,B,C], fill=False, closed=True, linewidth=1.5, edgecolor="black"))
    ax.plot([0,0.22,0.22],[0.22,0.22,0],color="black",lw=1)
    ax.text(-0.18,2.35,"B",fontsize=10,fontstyle="italic")
    ax.text(-0.18,-0.2,"C",fontsize=10,fontstyle="italic")
    ax.text(2.55,-0.2,"A",fontsize=10,fontstyle="italic")
    ax.text(-0.22,1.1,"8",fontsize=9)
    ax.text(1.2,-0.28,"6",fontsize=9)
    ax.set_xlim(-0.45,3.0); ax.set_ylim(-0.45,2.8); ax.axis("off")
    fig.tight_layout(pad=0)
    fig.savefig(path, dpi=180, bbox_inches="tight", pad_inches=0.02, facecolor="white")
    plt.close(fig)


def example_data(asset_dir: Path | None = None) -> dict[str, Any]:
    asset_dir = asset_dir or ROOT / "assets"
    asset_dir.mkdir(parents=True, exist_ok=True)
    graph = asset_dir / "sample_parabola.png"
    triangle = asset_dir / "sample_triangle.png"
    make_graph_diagram(graph)
    make_triangle_diagram(triangle)
    return {
        "subject": "Математика",
        "title": "Пробный тест в формате ЦТ",
        "duration": "180 минут",
        "max_score": "100",
        "website_url": "https://edusfera.by",
        "instructions": "В каждом задании выберите один правильный ответ из пяти предложенных. Запишите букву ответа в бланк в конце теста.",
        "section_title": "Часть A. Задания с выбором одного правильного ответа",
        "include_answer_sheet": True,
        "questions": [
            {"number":"A1","prompt":"Вычислите значение выражения $2^3 \\cdot 5 - 4^2$.","options":[{"text":"4"},{"text":"8"},{"text":"9"},{"text":"16"},{"text":"24"}],"correct":"E","explanation":"Сначала выполняем возведение в степень: $2^3=8$, $4^2=16$. Затем $8\\cdot5-16=40-16=24$."},
            {"number":"A2","prompt":"Решите уравнение $(x-3)(x+1)=0$.","options":[{"text":"−3"},{"text":"−1"},{"text":"3"},{"text":"−3 и 1"},{"text":"−1 и 3"}],"correct":"E","explanation":"Произведение равно нулю, когда хотя бы один множитель равен нулю: $x-3=0$ или $x+1=0$. Поэтому $x=3$ или $x=-1$."},
            {"number":"A3","prompt":"Упростите выражение $\\frac{x^2-9}{x-3}$ при $x\\ne3$.","options":[{"text":"$x-3$"},{"text":"$x+3$"},{"text":"$x^2+3$"},{"text":"$x$"},{"text":"$x^2-3$"}],"correct":"B","explanation":"Разложим числитель: $x^2-9=(x-3)(x+3)$. При $x\\ne3$ сокращаем общий множитель и получаем $x+3$."},
            {"number":"A4","prompt":"На рисунке изображён график функции $y=f(x)$. Укажите множество её нулей.","diagram_path":str(graph.relative_to(ROOT)),"options":[{"text":"{−1}"},{"text":"{2}"},{"text":"{−1; 2}"},{"text":"(−1; 2)"},{"text":"Пустое множество"}],"correct":"C","explanation":"Нули функции — значения $x$, при которых график пересекает ось $Ox$. На рисунке пересечения происходят при $x=-1$ и $x=2$."},
            {"number":"A5","diagram_height":78,"prompt":"В треугольнике $ABC$ угол $C$ равен $90^\\circ$, $AC=6$, $BC=8$. Найдите длину гипотенузы $AB$.","diagram_path":str(triangle.relative_to(ROOT)),"options":[{"text":"10"},{"text":"12"},{"text":"14"},{"text":"$\\sqrt{52}$"},{"text":"$5\\sqrt{2}$"}],"correct":"A","explanation":"По теореме Пифагора $AB^2=AC^2+BC^2=6^2+8^2=36+64=100$. Так как длина положительна, $AB=10$."},
        ]
    }


def write_example_files():
    data = example_data(ROOT / "assets")
    (ROOT / "examples" / "example_test.json").write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    return generate_pdfs(data, ROOT / "output")


def main():
    import argparse
    parser = argparse.ArgumentParser(description="EDUSFERA PDF Test Generator")
    parser.add_argument("--json", type=str, help="Path to input JSON file")
    parser.add_argument("--out-dir", type=str, default=str(ROOT / "output"), help="Output directory for generated PDFs")
    parser.add_argument("--merge", action="store_true", help="Merge student test and teacher key into single PDF")
    parser.add_argument("--out-merged", type=str, help="Path for merged output PDF")
    args = parser.parse_args()

    if args.json:
        json_path = Path(args.json)
        data = json.loads(json_path.read_text(encoding="utf-8"))
        out_dir = Path(args.out_dir)
        student, key = generate_pdfs(data, out_dir)
        print(f"STUDENT_PDF:{student.resolve()}")
        print(f"KEY_PDF:{key.resolve()}")

        if args.merge or args.out_merged:
            from pypdf import PdfWriter
            writer = PdfWriter()
            writer.append(str(student))
            writer.append(str(key))
            target_merged = Path(args.out_merged) if args.out_merged else (out_dir / "edusfera_test_complete.pdf")
            target_merged.parent.mkdir(parents=True, exist_ok=True)
            with open(target_merged, "wb") as f:
                writer.write(f)
            print(f"MERGED_PDF:{target_merged.resolve()}")
    else:
        student, key = write_example_files()
        print(f"STUDENT_PDF:{student.resolve()}")
        print(f"KEY_PDF:{key.resolve()}")


if __name__ == "__main__":
    main()
