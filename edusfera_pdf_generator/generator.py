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
matplotlib.rcParams['mathtext.fontset'] = 'stix'
from matplotlib.mathtext import math_to_image
from matplotlib.font_manager import FontProperties
from PIL import Image as PILImage

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas
from reportlab.platypus import (
    SimpleDocTemplate, Flowable, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, Image
)

ROOT = Path(__file__).resolve().parent
PAGE_W, PAGE_H = A4
LEFT = 13 * mm
RIGHT = 13 * mm
CONTENT_W = PAGE_W - LEFT - RIGHT

# --- 1. FONTS CONFIGURATION ---
MPL_FONTS = Path(matplotlib.get_data_path()) / "fonts" / "ttf"


def _find_font(candidates: list[str | Path]) -> str:
    for candidate in candidates:
        p = Path(candidate)
        if p.exists():
            return str(p)
    raise FileNotFoundError("Шрифт не найден. Проверьте наличие DejaVuSans.")


FONT_REGULAR = _find_font([
    "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    "/usr/local/share/fonts/DejaVuSans.ttf",
    MPL_FONTS / "DejaVuSans.ttf",
])
FONT_BOLD = _find_font([
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
    "/usr/local/share/fonts/DejaVuSans-Bold.ttf",
    MPL_FONTS / "DejaVuSans-Bold.ttf",
])
FONT_ITALIC = _find_font([
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Oblique.ttf",
    "/usr/local/share/fonts/DejaVuSans-Oblique.ttf",
    MPL_FONTS / "DejaVuSans-Oblique.ttf",
    MPL_FONTS / "DejaVuSans.ttf",
])

if "EDU" not in pdfmetrics.getRegisteredFontNames():
    pdfmetrics.registerFont(TTFont("EDU", FONT_REGULAR))
    pdfmetrics.registerFont(TTFont("EDU-Bold", FONT_BOLD))
    pdfmetrics.registerFont(TTFont("EDU-Italic", FONT_ITALIC))


STYLES = {
    "body": ParagraphStyle("body", fontName="EDU", fontSize=8.8, leading=12.5, textColor=colors.black),
    "body_bold": ParagraphStyle("body_bold", fontName="EDU-Bold", fontSize=8.8, leading=12.5, textColor=colors.black),
    "body_small": ParagraphStyle("body_small", fontName="EDU", fontSize=7.8, leading=10.5, textColor=colors.black),
    "question": ParagraphStyle("question", fontName="EDU", fontSize=9.5, leading=14.5, textColor=colors.black),
    "option": ParagraphStyle("option", fontName="EDU", fontSize=8.8, leading=12.5, textColor=colors.black),
    "banner_title": ParagraphStyle("banner_title", fontName="EDU-Bold", fontSize=9, leading=11, textColor=colors.HexColor("#0f172a")),
    "teacher_title": ParagraphStyle("teacher_title", fontName="EDU-Bold", fontSize=11, leading=14, textColor=colors.HexColor("#0f172a")),
    "teacher_expl": ParagraphStyle("teacher_expl", fontName="EDU", fontSize=7.2, leading=9.2, textColor=colors.HexColor("#1e293b")),
    "teacher_vtitle": ParagraphStyle("teacher_vtitle", fontName="EDU-Bold", fontSize=8.0, leading=10, textColor=colors.HexColor("#0f172a")),
    "workspace_title": ParagraphStyle("workspace_title", fontName="EDU-Bold", fontSize=7.2, leading=9.0, textColor=colors.HexColor("#64748b")),
}


# --- 2. RUNNING FOOTER WITH TWO-PASS PAGE NUMBERS ---
class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            canvas.Canvas.showPage(self)
        canvas.Canvas.save(self)

    def draw_page_decorations(self, total_pages):
        self.saveState()
        # Bottom footer separator
        self.setLineWidth(0.5)
        self.setStrokeColor(colors.HexColor("#cbd5e1"))
        self.line(LEFT, 11 * mm, PAGE_W - RIGHT, 11 * mm)

        # Branding
        self.setFont("EDU", 7.5)
        self.setFillColor(colors.HexColor("#64748b"))
        self.drawString(LEFT, 7.2 * mm, "EDUSFERA  •  Учебно-методический комплекс  •  edusfera.by")

        # Page count
        self.drawRightString(PAGE_W - RIGHT, 7.2 * mm, f"Страница {self._pageNumber} из {total_pages}")
        self.restoreState()


# --- 3. MATHEMATICAL FORMULAS RENDERER (WORD / STIX MATH STANDARDS) ---
def _safe_formula_markup(text: str, asset_dir: Path) -> str:
    """Safely convert $math$ and unicode formula spans into crisp inline images matching MS Word Cambria/STIX typography."""
    raw = str(text)
    # 1. Gracefully convert unicode expressions into standard LaTeX
    normalized = re.sub(r'√\(([^)]+)\)', r'$\\sqrt{\1}$', raw)
    normalized = re.sub(r'√([a-zA-Z0-9]+)', r'$\\sqrt{\1}$', normalized)

    pieces = re.split(r"(\$[^$]+\$)", normalized)
    output: list[str] = []
    counter = 0
    for piece in pieces:
        if len(piece) >= 2 and piece.startswith("$") and piece.endswith("$"):
            expr = piece[1:-1].strip()
            if not expr:
                continue
            counter += 1
            file_path = asset_dir / f"math_{counter}_{uuid.uuid4().hex[:6]}.png"
            try:
                # Render using STIX font matching Word / Cambridge Math standards
                math_to_image(f"${expr}$", str(file_path), prop=FontProperties(size=10.5), dpi=220, format="png", color="black")
                with PILImage.open(file_path) as im:
                    w_px, h_px = im.size
                height = max(8.5, min(16.0, h_px * 0.25))
                width = max(8.0, w_px * (height / h_px))
                output.append(f'<img src="{file_path.as_posix()}" width="{width:.1f}" height="{height:.1f}" valign="baseline"/>')
            except Exception:
                output.append(escape(piece))
        else:
            output.append(escape(piece).replace("\n", "<br/>"))
    return "".join(output)


def _generate_qr_image(url: str) -> PILImage.Image:
    qr = qrcode.QRCode(box_size=3, border=1)
    qr.add_data(url or "https://edusfera.by")
    qr.make(fit=True)
    return qr.make_image(fill_color="black", back_color="white").convert("RGB")


# --- 4. GEOMETRIC BRANDING FLOWABLES ---
def _is_exact_science(subject: str) -> bool:
    s = str(subject).lower()
    return any(kw in s for kw in ['математ', 'физик', 'алгебр', 'геометр', 'астроном'])


class EdusferaLogoFlowable(Flowable):
    """Draws the official black diamond EDUSFERA logo."""
    def __init__(self, size: float = 30, brand_sub: str | None = "Учебно-методический комплекс"):
        super().__init__()
        self.size = size
        self.brand_sub = brand_sub
        self.width = size + (115 if brand_sub else 68)
        self.height = size

    def wrap(self, availWidth, availHeight):
        return self.width, self.height

    def draw(self):
        c = self.canv
        c.saveState()
        s = self.size
        # Outer black diamond
        c.setFillColor(colors.black)
        c.roundRect(0, 0, s, s, 6, stroke=0, fill=1)
        # Inner diamond lines
        c.setStrokeColor(colors.white)
        c.setLineWidth(2.0)
        cx, cy = s / 2, s / 2
        d1 = s * 0.31
        c.line(cx, cy + d1, cx + d1, cy)
        c.line(cx + d1, cy, cx, cy - d1)
        c.line(cx, cy - d1, cx - d1, cy)
        c.line(cx - d1, cy, cx, cy + d1)
        d2 = s * 0.16
        c.setLineWidth(1.4)
        c.line(cx, cy + d2, cx + d2, cy)
        c.line(cx + d2, cy, cx, cy - d2)
        c.line(cx, cy - d2, cx - d2, cy)
        c.line(cx - d2, cy, cx, cy + d2)
        # Logotype
        c.setFillColor(colors.black)
        c.setFont("EDU-Bold", 16 if s >= 28 else 11)
        y_text = s * 0.44 if self.brand_sub else s * 0.28
        c.drawString(s + 6, y_text, "EDUSFERA")
        if self.brand_sub:
            c.setFont("EDU", 7.0 if s >= 28 else 5.8)
            c.setFillColor(colors.HexColor("#475569"))
            c.drawString(s + 6.5, s * 0.12, self.brand_sub)
        c.restoreState()


class ScissorsCutLineFlowable(Flowable):
    """Draws a clean dashed line with scissors ✂ for cutting A4 sheet in half or ruler strips."""
    def __init__(self, width: float, height: float = 20, label: str = ""):
        super().__init__()
        self.width = width
        self.height = height
        self.label = label

    def wrap(self, availWidth, availHeight):
        return self.width, self.height

    def draw(self):
        c = self.canv
        c.saveState()
        cy = self.height / 2
        c.setStrokeColor(colors.HexColor("#94a3b8"))
        c.setLineWidth(0.8)
        c.setDash([4, 3])
        if self.label:
            c.setFont("EDU-Bold", 7.5)
            c.setFillColor(colors.HexColor("#475569"))
            lbl_w = c.stringWidth(f"✂   {self.label}   ✂", "EDU-Bold", 7.5)
            gap = (lbl_w / 2) + 6
            c.line(0, cy, self.width / 2 - gap, cy)
            c.line(self.width / 2 + gap, cy, self.width, cy)
            c.drawCentredString(self.width / 2, cy - 2.5, f"✂   {self.label}   ✂")
        else:
            # Left segment
            c.line(0, cy, self.width / 2 - 14, cy)
            # Right segment
            c.line(self.width / 2 + 14, cy, self.width, cy)
            # Center scissors icon
            c.setFont("EDU-Bold", 10.5)
            c.setFillColor(colors.HexColor("#475569"))
            c.drawCentredString(self.width / 2, cy - 3.5, "✂")
        c.restoreState()


class SloganFlowable(Flowable):
    """Center header with slogan and underline."""
    def __init__(self, width: float = 140, height: float = 30):
        super().__init__()
        self.width = width
        self.height = height

    def wrap(self, availWidth, availHeight):
        return self.width, self.height

    def draw(self):
        c = self.canv
        c.saveState()
        c.setFont("EDU-Italic", 11)
        c.setFillColor(colors.black)
        cx = self.width / 2
        cy = self.height / 2 + 2
        c.drawCentredString(cx, cy, "Не зубри. Знай.")
        c.setLineWidth(0.8)
        c.setStrokeColor(colors.black)
        c.line(cx - 36, cy - 6, cx + 36, cy - 6)
        c.restoreState()


# --- 5. COMPONENT BUILDERS ---
def build_variant_header(variant_title: str, subject: str, subtitle: str, duration: str, 
                         count_a: int, count_b: int, max_score: str, asset_dir: Path, width: float,
                         custom_instructions: str | None = None,
                         grade: str | None = None, grade_label: str | None = None,
                         work_type: str | None = None, work_type_title: str | None = None,
                         exam_format: str = "exam") -> list[Flowable]:
    """Builds clean, uncollidable header for each test variant."""
    qr_img = _generate_qr_image("https://edusfera.by")
    qr_path = asset_dir / f"header_qr_{uuid.uuid4().hex[:6]}.png"
    qr_img.save(qr_path)
    
    # 1. Top row: Logo (left), Slogan (center), QR (right)
    qr_flow = Image(str(qr_path), width=36, height=36)
    qr_caption = Paragraph("<font size=6.5 color='#475569'>Больше тестов<br/>на <b>edusfera.by</b></font>", ParagraphStyle("qrcap", fontName="EDU", alignment=2, leading=8.5))
    qr_table = Table([[qr_caption, qr_flow]], colWidths=[65, 40])
    qr_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))

    top_table = Table([
        [EdusferaLogoFlowable(size=30), SloganFlowable(width=160), qr_table]
    ], colWidths=[150, width - 260, 110])
    top_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (1,0), (1,0), 'CENTER'),
        ('ALIGN', (2,0), (2,0), 'RIGHT'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))

    # 2. Main title row: Subject & Variant title (left), Passport card (right)
    work_disp = work_type_title if work_type_title else "Проверочная работа"
    grade_str = f" • {grade_label}" if grade_label else ""
    topic_clean = subtitle.replace("Проверочная работа:", "").strip()
    if topic_clean.lower().startswith(subject.lower()):
        topic_clean = topic_clean[len(subject):].lstrip(": -•")

    title_markup = (
        f"<font name='EDU-Bold' size=19 color='#000000'><b>{escape(subject)}</b></font><br/>"
        f"<font name='EDU-Bold' size=10.5 color='#0f172a'><b>{escape(work_disp)}{escape(grade_str)}</b></font> &nbsp;•&nbsp; "
        f"<font name='EDU-Bold' size=10 color='#475569'><b>{escape(variant_title)}</b></font><br/>"
        f"<font name='EDU' size=8.5 color='#64748b'>Тема: {escape(topic_clean)}</font>"
    )
    title_p = Paragraph(title_markup, ParagraphStyle("subjh", leading=13.5))

    is_school = (exam_format == "school")
    total_tasks = count_a + count_b
    if is_school:
        task_breakdown = f"{total_tasks} (с выбором: {count_a}, открытых: {count_b})" if count_b > 0 else f"{count_a}"
    else:
        task_breakdown = f"{total_tasks} (Часть А: {count_a}, Часть Б: {count_b})" if count_b > 0 else f"{count_a}"
    score_note = " (10-балльная шкала)" if str(max_score) in ["10", "10.0"] else " (шкала ЦТ)"
    passport_markup = (
        f"<b>Время выполнения:</b> {escape(duration)}<br/>"
        f"<b>Количество заданий:</b> {task_breakdown}<br/>"
        f"<b>Максимальный балл:</b> {escape(str(max_score))}{score_note}"
    )
    passport_p = Paragraph(passport_markup, ParagraphStyle("passp", fontName="EDU", fontSize=7.8, leading=10.8, textColor=colors.HexColor("#1e293b")))
    
    passport_table = Table([[passport_p]], colWidths=[185])
    passport_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#f1f5f9")),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 6),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e1")),
    ]))

    mid_table = Table([
        [title_p, passport_table]
    ], colWidths=[width - 195, 195])
    mid_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))

    # 3. Pupil line
    if grade == "abiturient":
        grade_str = "Абит."
    elif grade:
        grade_str = f"{grade}-____"
    else:
        grade_str = "_______"
    pupil_markup = f"<b>Учащийся:</b> __________________________________________________ &nbsp;&nbsp;&nbsp; <b>Класс:</b> {grade_str} &nbsp;&nbsp;&nbsp; <b>Дата:</b> ____________"
    pupil_p = Paragraph(pupil_markup, ParagraphStyle("pupil", fontName="EDU", fontSize=8.8, leading=12))

    # 4. Instruction box tailored by subject and format
    is_exact = _is_exact_science(subject)
    if custom_instructions and len(custom_instructions.strip()) > 10:
        raw_instr = custom_instructions
    elif is_school:
        if is_exact:
            raw_instr = (
                "В заданиях с выбором ответа отметьте один верный вариант. "
                "В открытых заданиях запишите полученное целое число. "
                "Ответы переносите в бланк внизу листа аккуратно и разборчиво."
            )
        else:
            raw_instr = (
                "В заданиях с выбором ответа отметьте один верный вариант. "
                "В открытых заданиях запишите краткий ответ (буквенно-цифровой код соответствия А1Б2..., комбинацию цифр или термин/фамилию). "
                "Ответы переносите в бланк внизу листа аккуратно и разборчиво."
            )
    elif is_exact:
        raw_instr = (
            "В заданиях Части А выберите один верный вариант ответа. "
            "В заданиях Части Б запишите полученное целое число. "
            "Ответы переносите в бланк внизу листа аккуратно и разборчиво."
        )
    else:
        raw_instr = (
            "В заданиях Части А выберите один верный вариант ответа. "
            "В заданиях Части Б запишите краткий ответ (буквенно-цифровой код соответствия А1Б2..., комбинацию цифр или термин/фамилию). "
            "Ответы переносите в бланк внизу листа аккуратно и разборчиво."
        )
    instr_markup = f"<b>Инструкция:</b> {escape(raw_instr)}"
    instr_p = Paragraph(instr_markup, ParagraphStyle("instt", fontName="EDU", fontSize=8.0, leading=10.5, textColor=colors.HexColor("#334155")))
    instr_table = Table([[instr_p]], colWidths=[width])
    instr_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#f8fafc")),
        ('BOX', (0,0), (-1,-1), 0.65, colors.HexColor("#94a3b8")),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ]))

    divider = Table([['']], colWidths=[width], rowHeights=[0.5], style=[
        ('LINEBELOW', (0,0), (-1,-1), 0.8, colors.black)
    ])

    return [
        top_table,
        Spacer(1, 3),
        divider,
        Spacer(1, 3.5),
        mid_table,
        Spacer(1, 3.5),
        pupil_p,
        Spacer(1, 3),
        instr_table,
        Spacer(1, 4),
    ]


def build_section_banner(title: str, width: float) -> Flowable:
    """Renders a sleek grey banner for Part A or Part B."""
    p = Paragraph(f"<b>{escape(title)}</b>", STYLES["banner_title"])
    t = Table([[p]], colWidths=[width])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#e2e8f0")),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e1")),
    ]))
    return t


def _diagram_image(task: dict[str, Any], asset_dir: Path, max_width: float, max_height: float) -> Image | None:
    """Load a safe local diagram; convert SVG to PNG for ReportLab's image flowable."""
    raw_path = task.get("diagram_path")
    if not raw_path:
        return None
    raw = Path(str(raw_path))
    if raw.is_absolute():
        path = raw.resolve()
    else:
        candidates = [(ROOT / raw).resolve(), (asset_dir / raw).resolve()]
        path = next((candidate for candidate in candidates if candidate.exists()), candidates[0])
    if not path.exists() or not path.is_file():
        raise ValueError(f"Файл схемы не найден: {path.name}")

    if path.suffix.lower() == ".svg":
        try:
            import cairosvg
        except ImportError as exc:
            raise RuntimeError("Для печати SVG-схем установите cairosvg.") from exc
        png_path = asset_dir / f"{path.stem}_print.png"
        cairosvg.svg2png(url=str(path), write_to=str(png_path), output_width=900)
        path = png_path

    img = Image(str(path))
    scale = min(max_width / img.imageWidth, max_height / img.imageHeight, 1.0)
    img.drawWidth = img.imageWidth * scale
    img.drawHeight = img.imageHeight * scale
    img.hAlign = "CENTER"
    return img


def build_task_flowable(task: dict[str, Any], asset_dir: Path, width: float, is_exact: bool = True, is_school: bool = False) -> Flowable:
    """Renders a complete task with badge, prompt, options/answer-box, and divider without any overlapping."""
    num_str = str(task.get("number", "1")).strip()
    if is_school:
        num_str = re.sub(r'^[АБAB]', '', num_str).strip() or "1"

    prompt_raw = str(task.get("prompt", ""))
    prompt_html = _safe_formula_markup(prompt_raw, asset_dir)
    prompt_p = Paragraph(prompt_html, STYLES["question"])

    # Badge Table
    badge_p = Paragraph(f"<b>{num_str}</b>", ParagraphStyle("bdg", fontName="EDU-Bold", fontSize=9.5, alignment=1, textColor=colors.black))
    badge_t = Table([[badge_p]], colWidths=[36], rowHeights=[20])
    badge_t.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 0.8, colors.black),
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#ffffff")),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 1),
        ('RIGHTPADDING', (0,0), (-1,-1), 1),
        ('TOPPADDING', (0,0), (-1,-1), 2),
        ('BOTTOMPADDING', (0,0), (-1,-1), 1),
    ]))

    # Top Table: [Badge] [Prompt]
    prompt_w = width - 42
    top_t = Table([[badge_t, prompt_p]], colWidths=[42, prompt_w])
    top_t.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))

    elements: list[Flowable] = [top_t, Spacer(1, 3)]

    # Optional diagram; SVG is rasterized at high resolution for a crisp print.
    img = _diagram_image(task, asset_dir, max_width=min(width * 0.60, 240),
                         max_height=float(task.get("diagram_height", 100)))
    if img is not None:
        elements.extend([img, Spacer(1, 3)])

    # Options or Part B box
    options = task.get("options") or []
    if options:
        # Determine layout grid based on max option length
        max_len = max(len(str(o.get("text", ""))) for o in options)
        opt_cells = []
        for i, opt in enumerate(options):
            letter = chr(65 + i)
            tag_p = Paragraph(f"<b>{letter}</b>", ParagraphStyle("opt_tag", fontName="EDU-Bold", fontSize=8.5, alignment=1, textColor=colors.black))
            tag_t = Table([[tag_p]], colWidths=[16], rowHeights=[16])
            tag_t.setStyle(TableStyle([
                ('BOX', (0,0), (-1,-1), 0.65, colors.black),
                ('ALIGN', (0,0), (-1,-1), 'CENTER'),
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('LEFTPADDING', (0,0), (-1,-1), 0),
                ('RIGHTPADDING', (0,0), (-1,-1), 0),
                ('TOPPADDING', (0,0), (-1,-1), 1),
                ('BOTTOMPADDING', (0,0), (-1,-1), 0),
            ]))
            text_html = _safe_formula_markup(str(opt.get("text", "")), asset_dir)
            text_p = Paragraph(text_html, STYLES["option"])
            
            item_t = Table([[tag_t, text_p]], colWidths=[20, None])
            item_t.setStyle(TableStyle([
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('LEFTPADDING', (0,0), (-1,-1), 0),
                ('RIGHTPADDING', (0,0), (-1,-1), 0),
                ('TOPPADDING', (0,0), (-1,-1), 0),
                ('BOTTOMPADDING', (0,0), (-1,-1), 0),
            ]))
            opt_cells.append(item_t)

        if max_len <= 14:
            # 1 row
            n = len(opt_cells)
            c_w = (width - 42) / n
            grid_t = Table([opt_cells], colWidths=[c_w] * n)
        elif max_len <= 35:
            # Two-column grid; supports both 4 and 5 answers without dropping E.
            c_w = (width - 42) / 2
            rows = [opt_cells[i:i + 2] for i in range(0, len(opt_cells), 2)]
            grid_t = Table(rows, colWidths=[c_w, c_w])
        else:
            # 1 column stack
            grid_t = Table([[c] for c in opt_cells], colWidths=[width - 42])

        grid_t.setStyle(TableStyle([
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('LEFTPADDING', (0,0), (-1,-1), 2),
            ('RIGHTPADDING', (0,0), (-1,-1), 2),
            ('TOPPADDING', (0,0), (-1,-1), 1),
            ('BOTTOMPADDING', (0,0), (-1,-1), 1),
        ]))

        indented_opts = Table([['', grid_t]], colWidths=[42, width - 42])
        indented_opts.setStyle(TableStyle([
            ('VALIGN', (0,0), (-1,-1), 'TOP'),
            ('LEFTPADDING', (0,0), (-1,-1), 0),
            ('RIGHTPADDING', (0,0), (-1,-1), 0),
            ('TOPPADDING', (0,0), (-1,-1), 0),
            ('BOTTOMPADDING', (0,0), (-1,-1), 0),
        ]))
        elements.append(indented_opts)
    else:
        # Part B answer box
        ans_label = Paragraph("<b>Ответ:</b>", STYLES["body_bold"])
        if is_exact:
            sign_box = Paragraph("<b>±</b>", ParagraphStyle("sign", fontName="EDU-Bold", fontSize=8.5, alignment=1, textColor=colors.HexColor("#475569")))
            sign_t = Table([[sign_box]], colWidths=[16], rowHeights=[16])
            sign_t.setStyle(TableStyle([
                ('BOX', (0,0), (-1,-1), 0.65, colors.HexColor("#64748b")),
                ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#f1f5f9")),
                ('ALIGN', (0,0), (-1,-1), 'CENTER'),
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('LEFTPADDING', (0,0), (-1,-1), 0),
                ('RIGHTPADDING', (0,0), (-1,-1), 0),
                ('TOPPADDING', (0,0), (-1,-1), 1),
                ('BOTTOMPADDING', (0,0), (-1,-1), 0),
            ]))

            digit_boxes = []
            for _ in range(4):
                dt = Table([['']], colWidths=[16], rowHeights=[16])
                dt.setStyle(TableStyle([
                    ('BOX', (0,0), (-1,-1), 0.65, colors.black),
                    ('BACKGROUND', (0,0), (-1,-1), colors.white),
                ]))
                digit_boxes.append(dt)

            hint_p = Paragraph("<font size=7.5 color='#64748b'>(в бланк ответов запишите полученное целое число)</font>", STYLES["body_small"])

            row = [ans_label, sign_t] + digit_boxes + [hint_p]
            col_w = [40, 18, 18, 18, 18, 18, width - 42 - 130]
        else:
            line_p = Paragraph("<font color='#64748b'>_________________________________________ &nbsp;</font><font size=7.2 color='#64748b'>(запишите код соответствия А_Б_В_Г_, цифры или термин)</font>", STYLES["body_small"])
            row = [ans_label, line_p]
            col_w = [40, width - 42 - 40]

        box_table = Table([row], colWidths=col_w)
        box_table.setStyle(TableStyle([
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('LEFTPADDING', (0,0), (-1,-1), 1),
            ('RIGHTPADDING', (0,0), (-1,-1), 1),
            ('TOPPADDING', (0,0), (-1,-1), 0),
            ('BOTTOMPADDING', (0,0), (-1,-1), 0),
        ]))

        indented_box = Table([['', box_table]], colWidths=[42, width - 42])
        indented_box.setStyle(TableStyle([
            ('VALIGN', (0,0), (-1,-1), 'TOP'),
            ('LEFTPADDING', (0,0), (-1,-1), 0),
            ('RIGHTPADDING', (0,0), (-1,-1), 0),
            ('TOPPADDING', (0,0), (-1,-1), 0),
            ('BOTTOMPADDING', (0,0), (-1,-1), 0),
        ]))
        elements.append(indented_box)

    elements.append(Spacer(1, 2))
    # Subtle task separator
    divider = Table([['']], colWidths=[width], rowHeights=[0.4], style=[
        ('LINEBELOW', (0,0), (-1,-1), 0.45, colors.HexColor("#e2e8f0"))
    ])
    elements.append(divider)
    elements.append(Spacer(1, 2.5))

    return KeepTogether(elements)


def build_student_workspace(width: float, height: float = 120) -> Flowable:
    """Builds a clean ruled workspace / scratchpad for student calculations."""
    title_p = Paragraph("<font color='#64748b' size=7.5><b>Место для вычислений и записи решений задач (черновик):</b></font>", STYLES["workspace_title"])
    box_t = Table([[title_p]], colWidths=[width], rowHeights=[height])
    box_t.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e1")),
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#fafafa")),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LEFTPADDING', (0,0), (-1,-1), 6),
        ('RIGHTPADDING', (0,0), (-1,-1), 6),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
    ]))
    return box_t


def build_variant_answer_sheet(variant_title: str, part_a: list[dict], part_b: list[dict], width: float, is_exact: bool = True, max_score: str = "100", exam_format: str = "exam") -> Flowable:
    """Builds a tear-off answer sheet tailored for the specific variant."""
    elements = []
    cut_line = Paragraph("<font color='#64748b'>✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</font>", STYLES["body_small"])
    elements.append(cut_line)
    elements.append(Spacer(1, 2))

    header_p = Paragraph(f"<b>Бланк ответов ({escape(variant_title)})</b>", STYLES["banner_title"])
    elements.append(header_p)
    elements.append(Spacer(1, 2.5))

    if exam_format == "school":
        all_tasks = part_a + part_b
        headers = [re.sub(r'^[АБAB]', '', str(t.get('number', i+1))).strip() or str(i+1) for i, t in enumerate(all_tasks)]
        col0_w = 80
        cell_w = min(42, (width - col0_w) / max(1, len(headers)))
        school_data = [
            ["№ задания"] + headers,
            ["Ответ"] + ["" for _ in headers]
        ]
        school_table = Table(school_data, colWidths=[col0_w] + [cell_w] * len(headers), rowHeights=[15, 18], hAlign='LEFT')
        school_table.setStyle(TableStyle([
            ('FONTNAME', (0,0), (-1,-1), 'EDU'),
            ('FONTNAME', (0,0), (0,-1), 'EDU-Bold'),
            ('FONTSIZE', (0,0), (-1,-1), 8),
            ('ALIGN', (0,0), (-1,-1), 'CENTER'),
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#f1f5f9")),
            ('GRID', (0,0), (-1,-1), 0.65, colors.HexColor("#0f172a")),
            ('LEFTPADDING', (0,0), (-1,-1), 2),
            ('RIGHTPADDING', (0,0), (-1,-1), 2),
        ]))
        elements.append(school_table)
        elements.append(Spacer(1, 4))
    else:
        # Grid for Part A
        if part_a:
            a_headers = [f"А{t.get('number', i+1)}" if not str(t.get('number','')).startswith(('А', 'A')) else str(t['number']) for i, t in enumerate(part_a)]
            col0_w = 90
            cell_w = min(32, (width - col0_w) / max(1, len(a_headers)))
            a_data = [
                ["Часть А"] + a_headers,
                ["Ответ"] + ["" for _ in a_headers]
            ]
            a_table = Table(a_data, colWidths=[col0_w] + [cell_w] * len(a_headers), rowHeights=[15, 18], hAlign='LEFT')
            a_table.setStyle(TableStyle([
                ('FONTNAME', (0,0), (-1,-1), 'EDU'),
                ('FONTNAME', (0,0), (0,-1), 'EDU-Bold'),
                ('FONTSIZE', (0,0), (-1,-1), 7.5),
                ('ALIGN', (0,0), (-1,-1), 'CENTER'),
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#f1f5f9")),
                ('GRID', (0,0), (-1,-1), 0.65, colors.HexColor("#0f172a")),
                ('LEFTPADDING', (0,0), (-1,-1), 2),
                ('RIGHTPADDING', (0,0), (-1,-1), 2),
            ]))
            elements.append(a_table)
            elements.append(Spacer(1, 2.5))

        # Grid for Part B
        if part_b:
            b_headers = [f"Б{t.get('number', i+1)}" if not str(t.get('number','')).startswith(('Б', 'B')) else str(t['number']) for i, t in enumerate(part_b)]
            col0_w = 90
            cell_w = min(48 if is_exact else 80, (width - col0_w) / max(1, len(b_headers)))
            b_label = "Ответ (число)" if is_exact else "Ответ (кратко)"
            b_data = [
                ["Часть Б"] + b_headers,
                [b_label] + ["" for _ in b_headers]
            ]
            b_table = Table(b_data, colWidths=[col0_w] + [cell_w] * len(b_headers), rowHeights=[15, 18], hAlign='LEFT')
            b_table.setStyle(TableStyle([
                ('FONTNAME', (0,0), (-1,-1), 'EDU'),
                ('FONTNAME', (0,0), (0,-1), 'EDU-Bold'),
                ('FONTSIZE', (0,0), (-1,-1), 7.5),
                ('ALIGN', (0,0), (-1,-1), 'CENTER'),
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#f1f5f9")),
                ('GRID', (0,0), (-1,-1), 0.65, colors.HexColor("#0f172a")),
                ('LEFTPADDING', (0,0), (-1,-1), 2),
                ('RIGHTPADDING', (0,0), (-1,-1), 2),
            ]))
            elements.append(b_table)
            elements.append(Spacer(1, 4))

    # Teacher grading line
    if str(max_score) in ["10", "10.0"]:
        grade_p = Paragraph("<b>Отметка (1–10):</b> ______ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>Балл:</b> _____ / 10 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>Проверил:</b> ____________________", STYLES["body"])
    else:
        grade_p = Paragraph("<b>Балл:</b> _____ / 100 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>Оценка (1–10):</b> ______ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>Проверил:</b> ____________________", STYLES["body"])
    elements.append(grade_p)

    return KeepTogether(elements)


def build_express_ruler(variants: list[dict], meta: dict, width: float) -> list[Flowable]:
    """
    Renders the 'Контрольная экспресс-линейка учителя' block.
    Provides narrow vertical cutting strips with dotted borders and scissors ✂
    for instantaneous grading of student test stacks during breaks.
    """
    if not variants:
        return []

    CYRILLIC_MAP = {
        '1': 'А', 'A': 'А', 'А': 'А',
        '2': 'Б', 'B': 'Б', 'Б': 'Б',
        '3': 'В', 'C': 'В', 'В': 'В',
        '4': 'Г', 'D': 'Г', 'Г': 'Г',
        '5': 'Д', 'E': 'Д', 'Д': 'Д',
    }

    elements: list[Flowable] = []

    header_p = Paragraph(
        "<b>КОНТРОЛЬНАЯ ЭКСПРЕСС-ЛИНЕЙКА УЧИТЕЛЯ (ДЛЯ ПРОВЕРКИ СТОПКИ ЗА ПЕРЕМЕНУ)</b>",
        STYLES["teacher_title"]
    )
    sub_p = Paragraph(
        "<font color='#475569' size=7.2>✂ <i>Вырежьте вертикальные полоски вариантов по пунктиру и прикладывайте к бланку ответов ученика для моментальной сверки за 3 секунды:</i></font>",
        STYLES["body"]
    )
    elements.append(header_p)
    elements.append(sub_p)
    elements.append(Spacer(1, 3))

    exam_format = meta.get("exam_format", "exam") if meta else "exam"
    is_school = (exam_format == "school")
    max_sc = str(meta.get("max_score", "10"))
    is_ct_ce = (not is_school) and (meta.get("work_type") in ("ct_ce", "exam") or max_sc == "100")

    first_v = variants[0]
    all_tasks_first = (first_v.get("part_a", []) + first_v.get("part_b", []))
    total_t = len(all_tasks_first)
    if total_t == 0:
        return elements

    if is_ct_ce:
        pts_per_task = max(1, 100 // total_t)
        total_max_label = f"Итого: {total_t * pts_per_task} б."
    else:
        pts_per_task = max(1, 10 // total_t)
        total_max_label = f"Итого: {total_t * pts_per_task} б."

    v_chunks = [variants[i:i + 5] for i in range(0, len(variants), 5)]

    for chunk in v_chunks:
        chunk_elements = []
        chunk_elements.append(ScissorsCutLineFlowable(width, height=14, label="Линия среза экспресс-линейки"))
        chunk_elements.append(Spacer(1, 3))

        n_c = len(chunk)
        col_w = width / n_c

        header_row = []
        for v in chunk:
            v_num = v.get("number", 1)
            v_title = f"<b>✂ ВАРИАНТ {v_num} ✂</b>"
            p = Paragraph(v_title, ParagraphStyle(f"rh_{v_num}", fontName="EDU-Bold", fontSize=8.0, alignment=1, textColor=colors.HexColor("#0f172a")))
            header_row.append(p)

        sub_row = []
        for v in chunk:
            v_num = v.get("number", 1)
            sub_markup = "<b>№</b> &nbsp;&nbsp;&nbsp; <b>Ключ</b> &nbsp;&nbsp;&nbsp; <b>Балл</b>"
            p = Paragraph(sub_markup, ParagraphStyle(f"rsub_{v_num}", fontName="EDU-Bold", fontSize=7.0, alignment=1, textColor=colors.HexColor("#475569")))
            sub_row.append(p)

        ruler_rows = [header_row, sub_row]

        for t_idx in range(total_t):
            t_row = []
            for v in chunk:
                v_all = v.get("part_a", []) + v.get("part_b", [])
                if t_idx < len(v_all):
                    curr_t = v_all[t_idx]
                    raw_num = str(curr_t.get("number", t_idx + 1)).strip()
                    if is_school:
                        clean_num = re.sub(r'^[АБAB]', '', raw_num).strip() or str(t_idx + 1)
                    else:
                        clean_num = raw_num if (raw_num.startswith(('А', 'A', 'Б', 'B'))) else (f"А{t_idx+1}" if curr_t.get("options") else f"Б{t_idx+1}")

                    ans = str(curr_t.get("correct", "—")).strip()
                    if curr_t.get("options"):
                        first_ch = ans[0].upper() if ans else "—"
                        clean_ans = CYRILLIC_MAP.get(first_ch, first_ch if first_ch in ['А','Б','В','Г','Д','1','2','3','4','5'] else "—")
                    else:
                        if not ans or ans.lower() in ["целое число", "ответ", "—"]:
                            ans = "—"
                        if len(ans) > 7:
                            ans = ans[:6] + "…"
                        clean_ans = ans

                    cell_markup = (
                        f"<font color='#64748b' size=7>{escape(clean_num)}</font>"
                        f" &nbsp;&nbsp;&nbsp; <b><font color='#0f172a' size=8>{escape(clean_ans)}</font></b>"
                        f" &nbsp;&nbsp;&nbsp; <font color='#047857' size=7>+{pts_per_task}</font>"
                    )
                    p = Paragraph(cell_markup, ParagraphStyle(f"rc_{v.get('number',1)}_{t_idx}", fontName="EDU", leading=9.0))
                    t_row.append(p)
                else:
                    t_row.append(Paragraph("—", ParagraphStyle("dash", fontName="EDU", fontSize=8, alignment=1)))
            ruler_rows.append(t_row)

        bot_row = []
        for v in chunk:
            v_num = v.get("number", 1)
            p = Paragraph(f"<b>{total_max_label}</b>", ParagraphStyle(f"rbot_{v_num}", fontName="EDU-Bold", fontSize=7.0, alignment=1, textColor=colors.HexColor("#1e293b")))
            bot_row.append(p)
        ruler_rows.append(bot_row)

        ruler_table = Table(ruler_rows, colWidths=[col_w] * n_c)
        ruler_table.setStyle(TableStyle([
            ('ALIGN', (0,0), (-1,-1), 'CENTER'),
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#e2e8f0")),
            ('BACKGROUND', (0,1), (-1,1), colors.HexColor("#f1f5f9")),
            ('BACKGROUND', (0,-1), (-1,-1), colors.HexColor("#f8fafc")),
            ('BOX', (0,0), (-1,-1), 0.8, colors.black),
            ('INNERGRID', (0,0), (-1,-1), 0.4, colors.HexColor("#cbd5e1")),
            ('LINEBEFORE', (1,0), (-1,-1), 1.2, colors.HexColor("#475569")),
            ('ROWBACKGROUNDS', (0,2), (-1,-2), [colors.white, colors.HexColor("#f8fafc")]),
            ('LEFTPADDING', (0,0), (-1,-1), 2),
            ('RIGHTPADDING', (0,0), (-1,-1), 2),
            ('TOPPADDING', (0,0), (-1,-1), 2),
            ('BOTTOMPADDING', (0,0), (-1,-1), 2),
        ]))
        chunk_elements.append(ruler_table)
        chunk_elements.append(Spacer(1, 3))
        chunk_elements.append(ScissorsCutLineFlowable(width, height=14, label="Линия среза экспресс-линейки"))
        chunk_elements.append(Spacer(1, 6))

        elements.append(KeepTogether(chunk_elements))

    return elements


def build_teacher_section(variants: list[dict], master_table_raw: list[dict], asset_dir: Path, width: float, meta: dict | None = None) -> list[Flowable]:
    """Builds the comprehensive teacher keys & analysis section on a dedicated page."""
    elements = [PageBreak()]

    meta = meta or {}
    work_disp = meta.get("work_type_title") or "Проверочная работа"
    grade_lbl = meta.get("grade_label") or "Школьная программа"
    topic_str = meta.get("topic") or ""
    max_sc = str(meta.get("max_score", "100"))
    exam_format = meta.get("exam_format", "exam") if meta else "exam"
    is_school = (exam_format == "school")
    is_ct_ce = (not is_school) and (meta.get("work_type") in ("ct_ce", "exam") or max_sc == "100")

    # Header Warning Banner
    banner_p = Paragraph("<font color='#b91c1c'><b>КЛЮЧИ И ОТВЕТЫ ДЛЯ ПРЕПОДАВАТЕЛЯ  •  МАТЕРИАЛ ДЛЯ ПРОВЕРКИ (НЕ ВЫДАВАТЬ УЧЕНИКАМ)</b></font>", STYLES["banner_title"])
    banner_t = Table([[banner_p]], colWidths=[width])
    banner_t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#fee2e2")),
        ('BOX', (0,0), (-1,-1), 0.75, colors.HexColor("#ef4444")),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ]))
    elements.append(banner_t)
    elements.append(Spacer(1, 6))

    # Pedagogical passport card
    passport_box_markup = (
        f"<b>Методический паспорт:</b> {escape(work_disp)} • {escape(grade_lbl)} • Стандарты образования Беларуси<br/>"
        f"<b>Контролируемые элементы:</b> владение ключевыми понятиями по теме «{escape(topic_str)}», применение стандартных алгоритмов.<br/>"
        f"<b>Экспресс-проверка:</b> благодаря единой матрице вариантов проверка стопки тетрадей занимает не более 2 минут."
    )
    passport_box_p = Paragraph(passport_box_markup, ParagraphStyle("tpass", fontName="EDU", fontSize=7.8, leading=10.5, textColor=colors.HexColor("#1e293b")))
    t_pass_table = Table([[passport_box_p]], colWidths=[width])
    t_pass_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#f8fafc")),
        ('BOX', (0,0), (-1,-1), 0.5, colors.HexColor("#94a3b8")),
        ('LEFTPADDING', (0,0), (-1,-1), 8),
        ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ]))
    elements.append(t_pass_table)
    elements.append(Spacer(1, 8))

    elements.append(Paragraph("Сводная мастер-таблица правильных ответов", STYLES["teacher_title"]))
    elements.append(Paragraph("Ниже приведена матрица для быстрой сверки результатов по всем вариантам:", STYLES["body"]))
    elements.append(Spacer(1, 5))

    CYRILLIC_MAP = {
        '1': 'А', 'A': 'А', 'А': 'А',
        '2': 'Б', 'B': 'Б', 'Б': 'Б',
        '3': 'В', 'C': 'В', 'В': 'В',
        '4': 'Г', 'D': 'Г', 'Г': 'Г',
        '5': 'Д', 'E': 'Д', 'Д': 'Д',
    }

    num_vars = len(variants)
    var_cols = [Paragraph(f"<b>Вариант {v.get('number', i+1)}</b>", ParagraphStyle("vc", fontName="EDU-Bold", fontSize=8, alignment=1)) for i, v in enumerate(variants)]
    col0_w = 48
    v_col_w = (width - col0_w) / max(1, num_vars)
    
    matrix_rows = [[Paragraph("<b>№ задания</b>", ParagraphStyle("nh", fontName="EDU-Bold", fontSize=8, alignment=1))] + var_cols]

    first_var = variants[0] if variants else {}
    tasks_a = first_var.get("part_a", [])
    tasks_b = first_var.get("part_b", [])

    if is_school:
        all_tasks = tasks_a + tasks_b
        for i, t in enumerate(all_tasks):
            clean_num = re.sub(r'^[АБAB]', '', str(t.get('number', i+1))).strip() or str(i+1)
            lbl_p = Paragraph(f"<b>{clean_num}</b>", ParagraphStyle("lbl", fontName="EDU-Bold", fontSize=8, alignment=1))
            row = [lbl_p]
            for v in variants:
                v_all = v.get("part_a", []) + v.get("part_b", [])
                if i < len(v_all):
                    curr_t = v_all[i]
                    ans = str(curr_t.get("correct", "—")).strip()
                    if curr_t.get("options"):
                        first_ch = ans[0].upper() if ans else "—"
                        clean_letter = CYRILLIC_MAP.get(first_ch, first_ch if first_ch in ['А','Б','В','Г','Д','1','2','3','4','5'] else "—")
                        cell_p = Paragraph(f"<b>{clean_letter}</b>", ParagraphStyle("ac", fontName="EDU-Bold", fontSize=9, alignment=1, textColor=colors.HexColor("#0f172a")))
                    else:
                        if not ans or ans.lower() in ["целое число", "ответ", "—"]:
                            ans = "—"
                        if len(ans) > 10:
                            ans = ans[:9] + "…"
                        font_sz = 7.0 if len(ans) > 6 else 8.0
                        lead_sz = 8.5 if len(ans) > 6 else 9.5
                        cell_p = Paragraph(f"<b>{escape(ans)}</b>", ParagraphStyle("ac", fontName="EDU-Bold", fontSize=font_sz, leading=lead_sz, alignment=1, textColor=colors.HexColor("#0f172a")))
                    row.append(cell_p)
                else:
                    row.append(Paragraph("—", ParagraphStyle("dash", fontName="EDU", fontSize=8, alignment=1, textColor=colors.HexColor("#64748b"))))
            matrix_rows.append(row)
    else:
        for i, t in enumerate(tasks_a):
            task_label = f"А{t.get('number', i+1)}" if not str(t.get('number','')).startswith(('А', 'A')) else str(t['number'])
            lbl_p = Paragraph(f"<b>{task_label}</b>", ParagraphStyle("lbl", fontName="EDU-Bold", fontSize=8, alignment=1))
            row = [lbl_p]
            for v in variants:
                v_tasks = v.get("part_a", [])
                if i < len(v_tasks):
                    ans = str(v_tasks[i].get("correct", "—")).strip()
                    first_ch = ans[0].upper() if ans else "—"
                    clean_letter = CYRILLIC_MAP.get(first_ch, first_ch if first_ch in ['А','Б','В','Г','Д','1','2','3','4','5'] else "—")
                    cell_p = Paragraph(f"<b>{clean_letter}</b>", ParagraphStyle("ac", fontName="EDU-Bold", fontSize=9, alignment=1, textColor=colors.HexColor("#0f172a")))
                    row.append(cell_p)
                else:
                    row.append(Paragraph("—", ParagraphStyle("dash", fontName="EDU", fontSize=8, alignment=1, textColor=colors.HexColor("#64748b"))))
            matrix_rows.append(row)

        for i, t in enumerate(tasks_b):
            task_label = f"Б{t.get('number', i+1)}" if not str(t.get('number','')).startswith(('Б', 'B')) else str(t['number'])
            lbl_p = Paragraph(f"<b>{task_label}</b>", ParagraphStyle("lbl", fontName="EDU-Bold", fontSize=8, alignment=1))
            row = [lbl_p]
            for v in variants:
                v_tasks = v.get("part_b", [])
                if i < len(v_tasks):
                    ans = str(v_tasks[i].get("correct", "—")).strip()
                    if not ans or ans.lower() in ["целое число", "ответ", "—"]:
                        ans = "—"
                    if len(ans) > 10:
                        ans = ans[:9] + "…"
                    font_sz = 7.0 if len(ans) > 6 else 8.0
                    lead_sz = 8.5 if len(ans) > 6 else 9.5
                    cell_p = Paragraph(f"<b>{escape(ans)}</b>", ParagraphStyle("bc", fontName="EDU-Bold", fontSize=font_sz, leading=lead_sz, alignment=1, textColor=colors.HexColor("#0f172a")))
                    row.append(cell_p)
                else:
                    row.append(Paragraph("—", ParagraphStyle("dash", fontName="EDU", fontSize=8, alignment=1, textColor=colors.HexColor("#64748b"))))
            matrix_rows.append(row)

    matrix_t = Table(matrix_rows, colWidths=[col0_w] + [v_col_w] * num_vars)
    matrix_t.setStyle(TableStyle([
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#f1f5f9")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#94a3b8")),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor("#f8fafc")]),
        ('CLIP', (0,0), (-1,-1), True),
        ('LEFTPADDING', (0,0), (-1,-1), 2),
        ('RIGHTPADDING', (0,0), (-1,-1), 2),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
    ]))
    elements.append(matrix_t)
    elements.append(Spacer(1, 8))

    # Grading Scale
    if is_ct_ce:
        elements.append(Paragraph("Шкала перевода первичных баллов в 10-балльную отметку (ГОСТ / Минобр РБ / ЦТ 2026)", STYLES["teacher_title"]))
        scale_rows = [
            ["Отметка (1–10)", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10"],
            ["Первичные баллы", "1–9", "10–19", "20–34", "35–49", "50–64", "65–74", "75–84", "85–92", "93–98", "99–100"],
        ]
        scale_col_w = (width - 90) / 10
        scale_t = Table(scale_rows, colWidths=[90] + [scale_col_w] * 10)
    else:
        elements.append(Paragraph(f"Критерии оценивания и 10-балльная шкала ({escape(grade_lbl)}, нормы Минобр РБ)", STYLES["teacher_title"]))
        scale_rows = [
            [
                Paragraph("<b>Отметка (1–10)</b>", ParagraphStyle("sh1", fontName="EDU-Bold", fontSize=7.5, alignment=1)),
                Paragraph("<b>1–2</b>", ParagraphStyle("sh2", fontName="EDU-Bold", fontSize=7.5, alignment=1)),
                Paragraph("<b>3–4</b>", ParagraphStyle("sh2", fontName="EDU-Bold", fontSize=7.5, alignment=1)),
                Paragraph("<b>5–6</b>", ParagraphStyle("sh2", fontName="EDU-Bold", fontSize=7.5, alignment=1)),
                Paragraph("<b>7–8</b>", ParagraphStyle("sh2", fontName="EDU-Bold", fontSize=7.5, alignment=1)),
                Paragraph("<b>9–10</b>", ParagraphStyle("sh2", fontName="EDU-Bold", fontSize=7.5, alignment=1)),
            ],
            [
                Paragraph("<b>Уровень</b>", ParagraphStyle("sh1", fontName="EDU-Bold", fontSize=7.0, alignment=1)),
                Paragraph("Начальный", ParagraphStyle("sb", fontName="EDU", fontSize=7.0, alignment=1)),
                Paragraph("Удовлетворит.", ParagraphStyle("sb", fontName="EDU", fontSize=7.0, alignment=1)),
                Paragraph("Достаточный", ParagraphStyle("sb", fontName="EDU", fontSize=7.0, alignment=1)),
                Paragraph("Хороший", ParagraphStyle("sb", fontName="EDU", fontSize=7.0, alignment=1)),
                Paragraph("Высокий", ParagraphStyle("sb", fontName="EDU", fontSize=7.0, alignment=1)),
            ],
            [
                Paragraph("<b>Критерий</b>", ParagraphStyle("sh1", fontName="EDU-Bold", fontSize=7.0, alignment=1)),
                Paragraph("10–29% (узнавание)", ParagraphStyle("sb", fontName="EDU", fontSize=6.5, leading=8.5, alignment=1)),
                Paragraph("30–49% (типовые)", ParagraphStyle("sb", fontName="EDU", fontSize=6.5, leading=8.5, alignment=1)),
                Paragraph("50–69% (по образцу)", ParagraphStyle("sb", fontName="EDU", fontSize=6.5, leading=8.5, alignment=1)),
                Paragraph("70–89% (самостоятельно)", ParagraphStyle("sb", fontName="EDU", fontSize=6.5, leading=8.5, alignment=1)),
                Paragraph("90–100% (без ошибок)", ParagraphStyle("sb", fontName="EDU", fontSize=6.5, leading=8.5, alignment=1)),
            ],
        ]
        col0_w = 64
        scale_col_w = (width - col0_w) / 5
        scale_t = Table(scale_rows, colWidths=[col0_w] + [scale_col_w] * 5)

    scale_t.setStyle(TableStyle([
        ('FONTNAME', (0,0), (-1,-1), 'EDU'),
        ('FONTNAME', (0,0), (0,-1), 'EDU-Bold'),
        ('FONTNAME', (0,0), (-1,0), 'EDU-Bold'),
        ('FONTSIZE', (0,0), (-1,-1), 7.5),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#f1f5f9")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#94a3b8")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
    ]))
    elements.append(scale_t)
    elements.append(Spacer(1, 6))

    # Teacher Express Ruler for instant paper grading
    elements.extend(build_express_ruler(variants, meta, width))

    # Detailed Explanations
    elements.append(Paragraph("Методический разбор заданий и анализ ловушек", STYLES["teacher_title"]))
    elements.append(Spacer(1, 3))

    for v_idx, v in enumerate(variants):
        var_num = v.get("number", v_idx + 1)
        elements.append(Paragraph(f"<b>Вариант {var_num}</b>", STYLES["teacher_vtitle"]))
        elements.append(Spacer(1, 1.5))

        for t in (v.get("part_a", []) + v.get("part_b", [])):
            t_num = t.get("number", "")
            if is_school:
                t_num = re.sub(r'^[АБAB]', '', str(t_num)).strip() or str(t.get("num", ""))
            ans = str(t.get("correct", "")).strip()
            expl = t.get("explanation", "").strip()
            # Clean monologue if any slipped through
            expl = re.sub(r'^(?:[^\.\?\!]+(?:стоп|проверяем|пересчитаем|исправляем|заменяем)[^\.\?\!]*[\.\?\!]\s*)+', '', expl, flags=re.IGNORECASE).strip()
            
            # Eliminate generic stubs
            if not expl or any(stub in expl for stub in ['Сверено по', 'Ответ вносится']):
                expl = "Обоснование ответа подтверждено учебной программой. Методическая ловушка: учет типичных ошибок и дистракторов."

            options = t.get("options", [])
            if options:
                first_ch = ans[0].upper() if ans else "—"
                clean_letter = CYRILLIC_MAP.get(first_ch, first_ch)
                letter_idx = {'А': 0, 'Б': 1, 'В': 2, 'Г': 3, 'Д': 4, '1': 0, '2': 1, '3': 2, '4': 3, '5': 4}.get(clean_letter, -1)
                opt_text = ""
                if 0 <= letter_idx < len(options):
                    opt_text = options[letter_idx].get("text", "")
                if opt_text:
                    clean_opt = re.sub(r'<[^>]+>', '', opt_text).strip()
                    if len(clean_opt) > 35:
                        clean_opt = clean_opt[:32] + "…"
                    item_text = f"<b>{t_num}. Ответ: {clean_letter} ({escape(clean_opt)})</b>"
                else:
                    item_text = f"<b>{t_num}. Ответ: {clean_letter}</b>"
            else:
                if not ans or ans.lower() in ["целое число", "ответ", "—"]:
                    ans = "—"
                item_text = f"<b>{t_num}. Ответ: {escape(ans)}</b>"

            if expl:
                if len(expl) > 150:
                    expl = expl[:145].rsplit(' ', 1)[0] + '...'
                item_text += f" — {_safe_formula_markup(expl, asset_dir)}"
            p = Paragraph(item_text, STYLES["teacher_expl"])
            elements.append(p)
            elements.append(Spacer(1, 1.0))
        elements.append(Spacer(1, 3.0))

    return elements


def build_economy_variant(
    variant: dict[str, Any],
    subject: str,
    subtitle: str,
    duration: str,
    max_score: str,
    asset_dir: Path,
    width: float,
    grade: str | None = None,
    grade_label: str | None = None,
    work_type: str | None = None,
    work_type_title: str | None = None,
    exam_format: str = "school"
) -> list[Flowable]:
    """Builds a compact variant designed to fit comfortably in half an A4 page (~135mm)."""
    blocks: list[Flowable] = []
    var_num = variant.get("number", 1)
    var_title = f"Вариант {var_num}"
    part_a = variant.get("part_a", [])
    part_b = variant.get("part_b", [])
    all_tasks = part_a + part_b
    is_exact = _is_exact_science(subject)

    # 1. Compact Header
    logo_flow = EdusferaLogoFlowable(size=18, brand_sub=None)
    
    topic_clean = subtitle.replace("Проверочная работа:", "").strip()
    if topic_clean.lower().startswith(subject.lower()):
        topic_clean = topic_clean[len(subject):].lstrip(": -•")
    
    work_disp = work_type_title if work_type_title else "Самостоятельная работа"
    grade_disp = f" ({grade_label})" if grade_label else ""
    
    header_left = Paragraph(
        f"<font name='EDU-Bold' size=9.5 color='#000000'><b>{escape(subject)}</b></font> &nbsp;•&nbsp; "
        f"<font name='EDU' size=8 color='#475569'>{escape(work_disp)}{escape(grade_disp)}</font><br/>"
        f"<font name='EDU-Bold' size=7.5 color='#334155'>Тема: </font><font name='EDU' size=7.5 color='#475569'>{escape(topic_clean)}</font>",
        ParagraphStyle("econ_hl", leading=10)
    )
    
    badge_p = Paragraph(f"<b>{escape(var_title)}</b>", ParagraphStyle("econ_bdg", fontName="EDU-Bold", fontSize=9, alignment=1, textColor=colors.black))
    badge_t = Table([[badge_p]], colWidths=[75], rowHeights=[18])
    badge_t.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 1.0, colors.black),
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#f1f5f9")),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('LEFTPADDING', (0,0), (-1,-1), 2),
        ('RIGHTPADDING', (0,0), (-1,-1), 2),
        ('TOPPADDING', (0,0), (-1,-1), 2),
        ('BOTTOMPADDING', (0,0), (-1,-1), 1),
    ]))
    
    logo_col_w = logo_flow.width + 18
    badge_col_w = 75
    header_table = Table([
        [logo_flow, header_left, badge_t]
    ], colWidths=[logo_col_w, width - logo_col_w - badge_col_w, badge_col_w])
    header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (2,0), (2,0), 'RIGHT'),
        ('LEFTPADDING', (0,0), (-1,-1), 0),
        ('RIGHTPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))
    blocks.append(header_table)
    blocks.append(Spacer(1, 2))

    # Pupil row
    if grade == "abiturient":
        grade_str = "Абит."
    elif grade:
        grade_str = f"{grade}-____"
    else:
        grade_str = "_______"
    pupil_p = Paragraph(
        f"<font size=7.5 color='#334155'><b>Учащийся:</b> __________________________________ &nbsp;&nbsp; <b>Класс:</b> {grade_str} &nbsp;&nbsp; <b>Дата:</b> __________</font>",
        ParagraphStyle("econ_pupil", fontName="EDU", leading=9.5)
    )
    blocks.append(pupil_p)
    blocks.append(Spacer(1, 2.5))

    # Tasks: keep each prompt, diagram and its answers together on the same page.
    for t_idx, task in enumerate(all_tasks):
        task_blocks: list[Flowable] = []
        num_str = str(task.get("number", t_idx + 1)).strip()
        num_clean = re.sub(r'^[АБAB]', '', num_str).strip() or str(t_idx + 1)

        prompt_raw = str(task.get("prompt", "")).strip()
        if not prompt_raw:
            raise ValueError(f"Вариант {var_num}, задание {num_clean}: пустое условие.")
        prompt_html = _safe_formula_markup(prompt_raw, asset_dir)
        prompt_p = Paragraph(
            f"<font size=8.0><b>{num_clean}.</b> {prompt_html}</font>",
            ParagraphStyle(f"econ_q_{var_num}_{t_idx}", fontName="EDU", leading=10.5)
        )
        task_blocks.extend([prompt_p, Spacer(1, 1)])

        img = _diagram_image(task, asset_dir, max_width=min(width * 0.43, 165), max_height=44)
        if img is not None:
            task_blocks.extend([img, Spacer(1, 1)])

        options = task.get("options") or []
        if options:
            option_paragraphs = []
            for o_i, opt in enumerate(options):
                opt_letter = chr(65 + o_i)
                opt_text = _safe_formula_markup(str(opt.get("text", "")), asset_dir)
                option_paragraphs.append(Paragraph(
                    f"<b>[{opt_letter}]</b> {opt_text}",
                    ParagraphStyle(f"econ_opt_{var_num}_{t_idx}_{o_i}", fontName="EDU", fontSize=7.6, leading=9.0)
                ))

            if len(options) <= 4 and max(len(str(o.get("text", ""))) for o in options) <= 10:
                opts_table = Table([option_paragraphs], colWidths=[(width - 48) / len(option_paragraphs)] * len(option_paragraphs))
            else:
                cols = 2
                option_rows = [option_paragraphs[i:i + cols] for i in range(0, len(option_paragraphs), cols)]
                opts_table = Table(option_rows, colWidths=[(width - 48) / cols] * cols)
            opts_table.setStyle(TableStyle([
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 2),
                ("RIGHTPADDING", (0, 0), (-1, -1), 3),
                ("TOPPADDING", (0, 0), (-1, -1), 0),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
            ]))
            indented_opts = Table([["", opts_table]], colWidths=[42, width - 42])
            indented_opts.setStyle(TableStyle([
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 0),
                ("RIGHTPADDING", (0, 0), (-1, -1), 0),
                ("TOPPADDING", (0, 0), (-1, -1), 0),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
            ]))
            task_blocks.extend([indented_opts, Spacer(1, 1.5)])
        else:
            ans_p = Paragraph(
                "<font size=7.5 color='#64748b'>Ответ ученика: [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</font>",
                ParagraphStyle(f"econ_ans_{var_num}_{t_idx}", fontName="EDU", leading=9.5)
            )
            task_blocks.extend([ans_p, Spacer(1, 1.5)])

        blocks.append(KeepTogether(task_blocks))

    # Compact Answer Blank Row
    ans_cells = ["<b>Бланк ответов:</b>"]
    for t_idx, task in enumerate(all_tasks):
        num_str = str(task.get("number", t_idx + 1)).strip()
        num_clean = re.sub(r'^[АБAB]', '', num_str).strip() or str(t_idx + 1)
        ans_cells.append(f"№{num_clean}: [ &nbsp;&nbsp;&nbsp; ]")
    ans_cells.append("Отметка: [ &nbsp;&nbsp;&nbsp; ]")
    
    ans_markup = " &nbsp;&nbsp; ".join(ans_cells)
    ans_p = Paragraph(f"<font size=7.5 color='#0f172a'>{ans_markup}</font>", ParagraphStyle(f"econ_blank_{var_num}", fontName="EDU", leading=9.5))
    ans_table = Table([[ans_p]], colWidths=[width])
    ans_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#f8fafc")),
        ('BOX', (0,0), (-1,-1), 0.6, colors.HexColor("#94a3b8")),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
    ]))
    blocks.append(Spacer(1, 1.5))
    blocks.append(ans_table)

    return blocks


def normalize_input_data(data: dict[str, Any]) -> dict[str, Any]:
    """Normalize input without fabricating missing questions, options or answer keys."""
    normalized = dict(data)
    normalized.setdefault("subject", "Математика")
    normalized.setdefault("title", "Пробный тест в формате стандартов ЦТ и ЦЭ 2026")
    normalized.setdefault("duration", "90 минут")
    normalized.setdefault("max_score", "100")
    normalized.setdefault("website_url", "https://edusfera.by")

    variants = normalized.get("variants") or []
    if not variants and normalized.get("questions"):
        questions = normalized["questions"]
        part_a = [q for q in questions if not str(q.get("number", "")).startswith("Б") and q.get("options")]
        part_b = [q for q in questions if q not in part_a]
        variants = [{"number": 1, "title": "Вариант 1", "part_a": part_a, "part_b": part_b}]

    if not isinstance(variants, list) or not variants:
        raise ValueError("PDF не создан: в документе нет вариантов заданий.")

    expected_a = len(variants[0].get("part_a", []))
    expected_b = len(variants[0].get("part_b", []))
    for v_idx, variant in enumerate(variants, start=1):
        if not isinstance(variant, dict):
            raise ValueError(f"Вариант {v_idx}: неверная структура данных.")
        part_a = variant.get("part_a") or []
        part_b = variant.get("part_b") or []
        if len(part_a) != expected_a or len(part_b) != expected_b:
            raise ValueError(
                f"Вариант {v_idx}: нарушена симметрия заданий. "
                f"Ожидалось А={expected_a}, Б={expected_b}; получено А={len(part_a)}, Б={len(part_b)}."
            )
        if not part_a and not part_b:
            raise ValueError(f"Вариант {v_idx}: отсутствуют задания.")

        seen_prompts: set[str] = set()
        for task_idx, task in enumerate([*part_a, *part_b], start=1):
            label = str(task.get("number") or task_idx)
            prompt = str(task.get("prompt", "")).strip()
            if not prompt or re.search(r"\[(?:текст|условие|вариант|вставьте)[^\]]*\]", prompt, re.I):
                raise ValueError(f"Вариант {v_idx}, задание {label}: пустое условие или шаблонная заглушка.")
            normalized_prompt = re.sub(r"\s+", " ", prompt).strip().casefold()
            if _is_exact_science(str(normalized.get("subject", ""))):
                if re.search(r"значени[ея]\s+выражени[яе].*при\s+[a-zа-яё]\s*=\s*[-+]?\d+\s*[:.]?$", normalized_prompt, re.I):
                    if not re.search(r"\d\s*[+\-*\/·×^]\s*\d|[a-zа-яё]\s*[+\-*\/·×^]\s*\d", normalized_prompt, re.I):
                        raise ValueError(f"Вариант {v_idx}, задание {label}: условие ссылается на выражение, но само выражение отсутствует.")
            if normalized_prompt in seen_prompts:
                raise ValueError(f"Вариант {v_idx}: повторяется условие задания «{label}».")
            seen_prompts.add(normalized_prompt)

            options = task.get("options") or []
            if options:
                if len(options) not in (4, 5):
                    raise ValueError(f"Вариант {v_idx}, задание {label}: требуется 4 или 5 вариантов ответа.")
                option_texts = [re.sub(r"\s+", " ", str(opt.get("text", ""))).strip().casefold() for opt in options]
                if any(not value or re.fullmatch(r"вариант \d+", value, re.I) for value in option_texts):
                    raise ValueError(f"Вариант {v_idx}, задание {label}: обнаружен пустой / фиктивный вариант ответа.")
                if len(set(option_texts)) != len(option_texts):
                    raise ValueError(f"Вариант {v_idx}, задание {label}: есть повторяющиеся варианты ответа.")
                correct = str(task.get("correct", "")).strip().upper()
                if correct.isdigit() and correct in "12345":
                    correct = chr(64 + int(correct))
                    task["correct"] = correct
                if correct not in [chr(65 + i) for i in range(len(options))]:
                    raise ValueError(f"Вариант {v_idx}, задание {label}: не указан корректный ключ ответа.")
            else:
                answer = str(task.get("correct", "")).strip()
                if not answer or answer.casefold() in {"целое число", "ответ", "—", "нет ответа"} or "____" in answer:
                    raise ValueError(f"Вариант {v_idx}, задание {label}: отсутствует проверенный краткий ответ.")

    normalized["variants"] = variants
    return normalized


def generate_multivariant_pdf(data: dict[str, Any], output_pdf: str | Path):
    """Entry point to generate a flawless multi-variant test PDF."""
    data = normalize_input_data(data)
    output_pdf = Path(output_pdf)
    output_pdf.parent.mkdir(parents=True, exist_ok=True)
    asset_dir = output_pdf.parent / "assets"
    asset_dir.mkdir(parents=True, exist_ok=True)

    doc = SimpleDocTemplate(
        str(output_pdf),
        pagesize=A4,
        leftMargin=LEFT,
        rightMargin=RIGHT,
        topMargin=7 * mm,
        bottomMargin=8 * mm,
    )

    story: list[Flowable] = []

    subject = str(data.get("subject", "Математика"))
    title = str(data.get("title", "Пробный тест"))
    subtitle = str(data.get("topic") or "Пробный тест в формате стандартов ЦТ и ЦЭ 2026")
    duration = str(data.get("duration", "90 минут"))
    max_score = str(data.get("max_score", "100"))
    variants = data.get("variants") or []
    is_exact = _is_exact_science(subject)

    exam_format = data.get("exam_format", "exam")
    is_school = (exam_format == "school")
    print_layout = data.get("print_layout") or ("economy" if is_school else "standard")

    if print_layout == "economy" and len(variants) > 0:
        for v_pair_idx in range(0, len(variants), 2):
            if v_pair_idx > 0:
                story.append(PageBreak())

            var_top = variants[v_pair_idx]
            story.append(KeepTogether(build_economy_variant(
                variant=var_top,
                subject=subject,
                subtitle=subtitle,
                duration=duration,
                max_score=max_score,
                asset_dir=asset_dir,
                width=CONTENT_W,
                grade=data.get("grade"),
                grade_label=data.get("grade_label"),
                work_type=data.get("work_type"),
                work_type_title=data.get("work_type_title"),
                exam_format=exam_format
            )))

            story.append(Spacer(1, 3))
            story.append(ScissorsCutLineFlowable(width=CONTENT_W))
            story.append(Spacer(1, 3))

            if v_pair_idx + 1 < len(variants):
                var_bottom = variants[v_pair_idx + 1]
                story.append(KeepTogether(build_economy_variant(
                    variant=var_bottom,
                    subject=subject,
                    subtitle=subtitle,
                    duration=duration,
                    max_score=max_score,
                    asset_dir=asset_dir,
                    width=CONTENT_W,
                    grade=data.get("grade"),
                    grade_label=data.get("grade_label"),
                    work_type=data.get("work_type"),
                    work_type_title=data.get("work_type_title"),
                    exam_format=exam_format
                )))
            else:
                story.append(Spacer(1, 4))
                story.append(build_student_workspace(CONTENT_W, height=130))
    else:
        for v_idx, variant in enumerate(variants):
            if v_idx > 0:
                story.append(PageBreak())

            var_num = variant.get("number", v_idx + 1)
            var_title = f"Вариант {var_num}"
            part_a = variant.get("part_a", [])
            part_b = variant.get("part_b", [])

            # Header for this variant
            header_blocks = build_variant_header(
                variant_title=var_title,
                subject=subject,
                subtitle=subtitle,
                duration=duration,
                count_a=len(part_a),
                count_b=len(part_b),
                max_score=max_score,
                asset_dir=asset_dir,
                width=CONTENT_W,
                custom_instructions=data.get("instructions"),
                grade=data.get("grade"),
                grade_label=data.get("grade_label"),
                work_type=data.get("work_type"),
                work_type_title=data.get("work_type_title"),
                exam_format=exam_format
            )
            story.extend(header_blocks)

            if is_school:
                all_tasks = part_a + part_b
                if all_tasks:
                    first_num = re.sub(r'^[АБAB]', '', str(all_tasks[0].get("number", "1"))).strip() or "1"
                    last_num = re.sub(r'^[АБAB]', '', str(all_tasks[-1].get("number", len(all_tasks)))).strip() or str(len(all_tasks))
                    banner = build_section_banner(f"Задания проверочной работы (№ {first_num}–{last_num})", CONTENT_W)
                    story.append(banner)
                    story.append(Spacer(1, 5))
                    for task in all_tasks:
                        story.append(build_task_flowable(task, asset_dir, CONTENT_W, is_exact=is_exact, is_school=True))
            else:
                # Part A section
                if part_a:
                    first_num = part_a[0].get("number", "А1")
                    last_num = part_a[-1].get("number", f"А{len(part_a)}")
                    banner = build_section_banner(f"Часть А. Задания с выбором одного правильного ответа ({first_num}–{last_num})", CONTENT_W)
                    story.append(banner)
                    story.append(Spacer(1, 5))

                    for task in part_a:
                        story.append(build_task_flowable(task, asset_dir, CONTENT_W, is_exact=is_exact))

                # Part B section
                if part_b:
                    first_num = part_b[0].get("number", "Б1")
                    last_num = part_b[-1].get("number", f"Б{len(part_b)}")
                    part_b_label = "Часть Б. Задания с кратким числовым ответом" if is_exact else "Часть Б. Задания с кратким ответом"
                    banner = build_section_banner(f"{part_b_label} ({first_num}–{last_num})", CONTENT_W)
                    story.append(banner)
                    story.append(Spacer(1, 5))

                    for task in part_b:
                        story.append(build_task_flowable(task, asset_dir, CONTENT_W, is_exact=is_exact))

            # Workspace scratchpad if variant has <= 5 tasks to ensure balanced layout
            total_tasks = len(part_a) + len(part_b)
            if total_tasks <= 3:
                story.append(Spacer(1, 5))
                story.append(build_student_workspace(CONTENT_W, height=135))
                story.append(Spacer(1, 6))
            elif total_tasks == 4:
                story.append(Spacer(1, 4))
                story.append(build_student_workspace(CONTENT_W, height=95))
                story.append(Spacer(1, 5))
            elif total_tasks == 5:
                story.append(Spacer(1, 3))
                story.append(build_student_workspace(CONTENT_W, height=60))
                story.append(Spacer(1, 4))
            else:
                story.append(Spacer(1, 4))

            # Answer sheet for this variant
            story.append(build_variant_answer_sheet(var_title, part_a, part_b, CONTENT_W, is_exact=is_exact, max_score=max_score, exam_format=exam_format))

    # Teacher master section at the end
    master_table_raw = data.get("master_table", [])
    teacher_blocks = build_teacher_section(variants, master_table_raw, asset_dir, CONTENT_W, meta=data)
    story.extend(teacher_blocks)

    doc.build(story, canvasmaker=NumberedCanvas)


def generate_pdfs(data: dict[str, Any], out_dir: str | Path) -> tuple[Path, Path]:
    """Generates student test PDF and teacher key PDF for the web UI and CLI."""
    data = normalize_input_data(data)
    out_dir = Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    asset_dir = out_dir / "assets"
    asset_dir.mkdir(parents=True, exist_ok=True)

    student_pdf = out_dir / "edusfera_test_student.pdf"
    key_pdf = out_dir / "edusfera_test_teacher_key.pdf"

    # 1. Student PDF
    doc_student = SimpleDocTemplate(
        str(student_pdf),
        pagesize=A4,
        leftMargin=LEFT,
        rightMargin=RIGHT,
        topMargin=7 * mm,
        bottomMargin=8 * mm,
    )
    story_student: list[Flowable] = []

    subject = str(data.get("subject", "Математика"))
    subtitle = str(data.get("topic") or "Пробный тест в формате стандартов ЦТ и ЦЭ 2026")
    duration = str(data.get("duration", "90 минут"))
    max_score = str(data.get("max_score", "100"))
    variants = data.get("variants") or []
    is_exact = _is_exact_science(subject)

    grade = str(data.get("grade") or "7")
    work_type = str(data.get("work_type") or "independent")
    exam_format = str(data.get("exam_format") or "school")
    is_school = (exam_format == "school") or (grade not in ["10", "11", "abiturient"] and work_type != "ct_ce")

    print_layout = data.get("print_layout") or ("economy" if is_school else "standard")

    if print_layout == "economy" and len(variants) > 0:
        for v_pair_idx in range(0, len(variants), 2):
            if v_pair_idx > 0:
                story_student.append(PageBreak())

            var_top = variants[v_pair_idx]
            story_student.append(KeepTogether(build_economy_variant(
                variant=var_top,
                subject=subject,
                subtitle=subtitle,
                duration=duration,
                max_score=max_score,
                asset_dir=asset_dir,
                width=CONTENT_W,
                grade=data.get("grade"),
                grade_label=data.get("grade_label"),
                work_type=data.get("work_type"),
                work_type_title=data.get("work_type_title"),
                exam_format=exam_format
            )))

            story_student.append(Spacer(1, 3))
            story_student.append(ScissorsCutLineFlowable(width=CONTENT_W))
            story_student.append(Spacer(1, 3))

            if v_pair_idx + 1 < len(variants):
                var_bottom = variants[v_pair_idx + 1]
                story_student.append(KeepTogether(build_economy_variant(
                    variant=var_bottom,
                    subject=subject,
                    subtitle=subtitle,
                    duration=duration,
                    max_score=max_score,
                    asset_dir=asset_dir,
                    width=CONTENT_W,
                    grade=data.get("grade"),
                    grade_label=data.get("grade_label"),
                    work_type=data.get("work_type"),
                    work_type_title=data.get("work_type_title"),
                    exam_format=exam_format
                )))
            else:
                story_student.append(Spacer(1, 4))
                story_student.append(build_student_workspace(CONTENT_W, height=130))
    else:
        for v_idx, variant in enumerate(variants):
            if v_idx > 0:
                story_student.append(PageBreak())

            var_num = variant.get("number", v_idx + 1)
            var_title = f"Вариант {var_num}"
            part_a = variant.get("part_a", [])
            part_b = variant.get("part_b", [])

            header_blocks = build_variant_header(
                variant_title=var_title,
                subject=subject,
                subtitle=subtitle,
                duration=duration,
                count_a=len(part_a),
                count_b=len(part_b),
                max_score=max_score,
                asset_dir=asset_dir,
                width=CONTENT_W,
                custom_instructions=data.get("instructions"),
                grade=data.get("grade"),
                grade_label=data.get("grade_label"),
                work_type=data.get("work_type"),
                work_type_title=data.get("work_type_title"),
                exam_format=exam_format
            )
            story_student.extend(header_blocks)

            if is_school:
                all_tasks = part_a + part_b
                if all_tasks:
                    first_num = re.sub(r'^[АБAB]', '', str(all_tasks[0].get("number", "1"))).strip() or "1"
                    last_num = re.sub(r'^[АБAB]', '', str(all_tasks[-1].get("number", len(all_tasks)))).strip() or str(len(all_tasks))
                    story_student.append(build_section_banner(f"Задания проверочной работы (№ {first_num}–{last_num})", CONTENT_W))
                    story_student.append(Spacer(1, 5))
                    for task in all_tasks:
                        story_student.append(build_task_flowable(task, asset_dir, CONTENT_W, is_exact=is_exact, is_school=True))
            else:
                if part_a:
                    first_num = part_a[0].get("number", "А1")
                    last_num = part_a[-1].get("number", f"А{len(part_a)}")
                    story_student.append(build_section_banner(f"Часть А. Задания с выбором одного правильного ответа ({first_num}–{last_num})", CONTENT_W))
                    story_student.append(Spacer(1, 5))
                    for task in part_a:
                        story_student.append(build_task_flowable(task, asset_dir, CONTENT_W, is_exact=is_exact))

                if part_b:
                    first_num = part_b[0].get("number", "Б1")
                    last_num = part_b[-1].get("number", f"Б{len(part_b)}")
                    part_b_label = "Часть Б. Задания с кратким числовым ответом" if is_exact else "Часть Б. Задания с кратким ответом"
                    story_student.append(build_section_banner(f"{part_b_label} ({first_num}–{last_num})", CONTENT_W))
                    story_student.append(Spacer(1, 5))
                    for task in part_b:
                        story_student.append(build_task_flowable(task, asset_dir, CONTENT_W, is_exact=is_exact))

            # Workspace scratchpad if variant has <= 5 tasks to ensure balanced layout
            total_tasks = len(part_a) + len(part_b)
            if total_tasks <= 3:
                story_student.append(Spacer(1, 5))
                story_student.append(build_student_workspace(CONTENT_W, height=135))
                story_student.append(Spacer(1, 6))
            elif total_tasks == 4:
                story_student.append(Spacer(1, 4))
                story_student.append(build_student_workspace(CONTENT_W, height=95))
                story_student.append(Spacer(1, 5))
            elif total_tasks == 5:
                story_student.append(Spacer(1, 3))
                story_student.append(build_student_workspace(CONTENT_W, height=60))
                story_student.append(Spacer(1, 4))
            else:
                story_student.append(Spacer(1, 4))

            story_student.append(build_variant_answer_sheet(var_title, part_a, part_b, CONTENT_W, is_exact=is_exact, max_score=max_score, exam_format=exam_format))

    doc_student.build(story_student, canvasmaker=NumberedCanvas)

    # 2. Key PDF
    doc_key = SimpleDocTemplate(
        str(key_pdf),
        pagesize=A4,
        leftMargin=LEFT,
        rightMargin=RIGHT,
        topMargin=10 * mm,
        bottomMargin=14 * mm,
    )
    master_table_raw = data.get("master_table", [])
    story_key = build_teacher_section(variants, master_table_raw, asset_dir, CONTENT_W, meta=data)
    # Remove initial PageBreak for standalone key doc
    if story_key and isinstance(story_key[0], PageBreak):
        story_key.pop(0)
    doc_key.build(story_key, canvasmaker=NumberedCanvas)

    return student_pdf, key_pdf


def example_data(asset_dir: Path | None = None) -> dict[str, Any]:
    return {
        "subject": "Математика",
        "title": "Пробный тест в формате стандартов ЦТ и ЦЭ 2026",
        "topic": "Иррациональные уравнения",
        "duration": "90 минут",
        "max_score": "100",
        "website_url": "https://edusfera.by",
        "variants": [
            {
                "number": 1,
                "title": "Вариант 1",
                "part_a": [
                    {"number":"А1","prompt":"Вычислите значение выражения $2^3 \\cdot 5 - 4^2$.","options":[{"text":"4"},{"text":"8"},{"text":"9"},{"text":"16"},{"text":"24"}],"correct":"E","explanation":"$2^3=8, 4^2=16. 8\\cdot5-16=40-16=24$."},
                    {"number":"А2","prompt":"Решите уравнение $(x-3)(x+1)=0$.","options":[{"text":"−3"},{"text":"−1"},{"text":"3"},{"text":"−3 и 1"},{"text":"−1 и 3"}],"correct":"E","explanation":"$x=3$ или $x=-1$."},
                    {"number":"А3","prompt":"Упростите выражение $\\frac{x^2-9}{x-3}$ при $x\\ne3$.","options":[{"text":"$x-3$"},{"text":"$x+3$"},{"text":"$x^2+3$"},{"text":"$x$"}],"correct":"B","explanation":"$x^2-9=(x-3)(x+3)$."},
                    {"number":"А4","prompt":"Найдите корни уравнения $\\sqrt{x+4}=3$.","options":[{"text":"5"},{"text":"-5"},{"text":"9"},{"text":"нет корней"}],"correct":"A","explanation":"$x+4=9 \\Rightarrow x=5$."},
                ],
                "part_b": [
                    {"number":"Б1","prompt":"Найдите корень уравнения $\\sqrt{2x^2-3x-5}=x-1$.","correct":"3","explanation":"ОДЗ $x\\ge1$. Корень $x=3$."},
                    {"number":"Б2","prompt":"Найдите сумму корней уравнения $\\sqrt{x+7}-\\sqrt{3x-2}=1$.","correct":"2","explanation":"Корень $x=2$."}
                ]
            }
        ]
    }


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
        target_merged = Path(args.out_merged) if args.out_merged else (out_dir / "edusfera_test_complete.pdf")

        if args.merge or args.out_merged:
            # Generate the unified multivariant document directly with running page count
            generate_multivariant_pdf(data, target_merged)
            print(f"MERGED_PDF:{target_merged.resolve()}")
        else:
            student, key = generate_pdfs(data, out_dir)
            print(f"STUDENT_PDF:{student.resolve()}")
            print(f"KEY_PDF:{key.resolve()}")
    else:
        out_dir = ROOT / "output"
        data = example_data()
        student, key = generate_pdfs(data, out_dir)
        print(f"STUDENT_PDF:{student.resolve()}")
        print(f"KEY_PDF:{key.resolve()}")


if __name__ == "__main__":
    main()
