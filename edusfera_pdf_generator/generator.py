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
    "teacher_title": ParagraphStyle("teacher_title", fontName="EDU-Bold", fontSize=12, leading=15, textColor=colors.HexColor("#0f172a")),
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
        self.drawString(LEFT, 7.2 * mm, "EDUSFERA  •  Платформа для репетиторов  •  edusfera.by")

        # Page count
        self.drawRightString(PAGE_W - RIGHT, 7.2 * mm, f"Страница {self._pageNumber} из {total_pages}")
        self.restoreState()


# --- 3. MATHEMATICAL FORMULAS RENDERER ---
def _safe_formula_markup(text: str, asset_dir: Path) -> str:
    """Safely convert $math$ spans into clean inline images and preserve unicode formulas."""
    pieces = re.split(r"(\$[^$]+\$)", str(text))
    output: list[str] = []
    counter = 0
    for piece in pieces:
        if len(piece) >= 2 and piece.startswith("$") and piece.endswith("$"):
            expr = piece[1:-1].strip()
            counter += 1
            file_path = asset_dir / f"math_{counter}_{uuid.uuid4().hex[:6]}.png"
            try:
                math_to_image(f"${expr}$", str(file_path), prop=FontProperties(size=10.5), dpi=180, format="png", color="black")
                with PILImage.open(file_path) as im:
                    w_px, h_px = im.size
                height = max(8.5, min(14.0, h_px * 0.28))
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
class EdusferaLogoFlowable(Flowable):
    """Draws the official black diamond EDUSFERA logo."""
    def __init__(self, size: float = 30):
        super().__init__()
        self.size = size
        self.width = size + 105
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
        c.setFont("EDU-Bold", 16)
        c.drawString(s + 8, s * 0.44, "EDUSFERA")
        c.setFont("EDU", 7.0)
        c.setFillColor(colors.HexColor("#475569"))
        c.drawString(s + 8.5, s * 0.12, "Платформа для репетиторов")
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
                         count_a: int, count_b: int, max_score: str, asset_dir: Path, width: float) -> list[Flowable]:
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
    title_markup = (
        f"<font name='EDU-Bold' size=22 color='#000000'><b>{escape(subject)}</b></font><br/>"
        f"<font name='EDU-Bold' size=11.5 color='#0f172a'><b>{escape(variant_title)}</b></font> &nbsp;•&nbsp; "
        f"<font name='EDU' size=9.5 color='#475569'>{escape(subtitle)}</font>"
    )
    title_p = Paragraph(title_markup, ParagraphStyle("subjh", leading=16))

    total_tasks = count_a + count_b
    task_breakdown = f"{total_tasks} (Часть А: {count_a}, Часть Б: {count_b})" if count_b > 0 else f"{count_a}"
    passport_markup = (
        f"<b>Время выполнения:</b> {escape(duration)}<br/>"
        f"<b>Количество заданий:</b> {task_breakdown}<br/>"
        f"<b>Максимальный балл:</b> {escape(str(max_score))}"
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
    pupil_markup = "<b>Учащийся:</b> __________________________________________________ &nbsp;&nbsp;&nbsp; <b>Класс:</b> _______ &nbsp;&nbsp;&nbsp; <b>Дата:</b> ____________"
    pupil_p = Paragraph(pupil_markup, ParagraphStyle("pupil", fontName="EDU", fontSize=8.8, leading=12))

    # 4. Instruction box
    instr_markup = (
        "<b>Инструкция:</b> В заданиях Части А выберите один верный вариант ответа. "
        "В заданиях Части Б запишите полученное целое число. "
        "Ответы переносите в бланк внизу листа аккуратно и разборчиво."
    )
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
        Spacer(1, 4),
        divider,
        Spacer(1, 5),
        mid_table,
        Spacer(1, 5),
        pupil_p,
        Spacer(1, 4),
        instr_table,
        Spacer(1, 6),
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


def build_task_flowable(task: dict[str, Any], asset_dir: Path, width: float) -> Flowable:
    """Renders a complete task with badge, prompt, options/answer-box, and divider without any overlapping."""
    num_str = str(task.get("number", "А1"))
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

    # Optional Diagram image
    diagram_path = task.get("diagram_path")
    if diagram_path and not Path(diagram_path).is_absolute():
        diagram_path = str((ROOT / diagram_path).resolve())
    if diagram_path and Path(diagram_path).exists():
        img = Image(str(diagram_path))
        max_w = min(width * 0.60, 240)
        max_h = float(task.get("diagram_height", 100))
        scale = min(max_w / img.imageWidth, max_h / img.imageHeight, 1.0)
        img.drawWidth = img.imageWidth * scale
        img.drawHeight = img.imageHeight * scale
        img.hAlign = "CENTER"
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
            # 2x2 grid
            row1 = opt_cells[:2]
            row2 = opt_cells[2:4]
            c_w = (width - 42) / 2
            grid_t = Table([row1, row2], colWidths=[c_w, c_w])
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

    elements.append(Spacer(1, 3))
    # Subtle task separator
    divider = Table([['']], colWidths=[width], rowHeights=[0.4], style=[
        ('LINEBELOW', (0,0), (-1,-1), 0.45, colors.HexColor("#e2e8f0"))
    ])
    elements.append(divider)
    elements.append(Spacer(1, 4))

    return KeepTogether(elements)


def build_variant_answer_sheet(variant_title: str, part_a: list[dict], part_b: list[dict], width: float) -> Flowable:
    """Builds a tear-off answer sheet tailored for the specific variant."""
    elements = []
    cut_line = Paragraph("<font color='#64748b'>✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</font>", STYLES["body_small"])
    elements.append(cut_line)
    elements.append(Spacer(1, 3))

    header_p = Paragraph(f"<b>Бланк ответов ({escape(variant_title)})</b>", STYLES["banner_title"])
    elements.append(header_p)
    elements.append(Spacer(1, 4))

    # Grid for Part A
    if part_a:
        a_headers = [f"А{t.get('number', i+1)}" if not str(t.get('number','')).startswith('А') else str(t['number']) for i, t in enumerate(part_a)]
        col0_w = 90
        cell_w = min(32, (width - col0_w) / max(1, len(a_headers)))
        a_data = [
            ["Часть А"] + a_headers,
            ["Ответ"] + ["" for _ in a_headers]
        ]
        a_table = Table(a_data, colWidths=[col0_w] + [cell_w] * len(a_headers), rowHeights=[17, 22], hAlign='LEFT')
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
        elements.append(Spacer(1, 4))

    # Grid for Part B
    if part_b:
        b_headers = [f"Б{t.get('number', i+1)}" if not str(t.get('number','')).startswith('Б') else str(t['number']) for i, t in enumerate(part_b)]
        col0_w = 90
        cell_w = min(48, (width - col0_w) / max(1, len(b_headers)))
        b_data = [
            ["Часть Б"] + b_headers,
            ["Ответ (число)"] + ["" for _ in b_headers]
        ]
        b_table = Table(b_data, colWidths=[col0_w] + [cell_w] * len(b_headers), rowHeights=[17, 22], hAlign='LEFT')
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
    grade_p = Paragraph("<b>Балл:</b> _____ / 100 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>Оценка (1–10):</b> ______ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>Проверил:</b> ____________________", STYLES["body"])
    elements.append(grade_p)

    return KeepTogether(elements)


def build_teacher_section(variants: list[dict], master_table_raw: list[dict], asset_dir: Path, width: float) -> list[Flowable]:
    """Builds the comprehensive teacher keys & analysis section on a dedicated page."""
    elements = [PageBreak()]

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
    elements.append(Spacer(1, 8))

    elements.append(Paragraph("Сводная мастер-таблица правильных ответов", STYLES["teacher_title"]))
    elements.append(Paragraph("Ниже приведена матрица для быстрой сверки результатов по всем вариантам:", STYLES["body"]))
    elements.append(Spacer(1, 5))

    num_vars = len(variants)
    var_cols = [f"Вариант {v.get('number', i+1)}" for i, v in enumerate(variants)]
    col0_w = 70
    v_col_w = (width - col0_w) / max(1, num_vars)
    
    matrix_rows = [["№ задания"] + var_cols]

    first_var = variants[0] if variants else {}
    tasks_a = first_var.get("part_a", [])
    tasks_b = first_var.get("part_b", [])

    for i, t in enumerate(tasks_a):
        task_label = f"А{t.get('number', i+1)}" if not str(t.get('number','')).startswith('А') else str(t['number'])
        row = [task_label]
        for v in variants:
            v_tasks = v.get("part_a", [])
            if i < len(v_tasks):
                ans = v_tasks[i].get("correct", "—")
                opts = v_tasks[i].get("options", [])
                opt_txt = ""
                if opts and len(ans) == 1 and ans.isalpha():
                    idx = ord(ans.upper()) - 65
                    if 0 <= idx < len(opts):
                        opt_txt = f" ({opts[idx].get('text','')})"
                row.append(f"{ans}{opt_txt}")
            else:
                row.append("—")
        matrix_rows.append(row)

    for i, t in enumerate(tasks_b):
        task_label = f"Б{t.get('number', i+1)}" if not str(t.get('number','')).startswith('Б') else str(t['number'])
        row = [task_label]
        for v in variants:
            v_tasks = v.get("part_b", [])
            if i < len(v_tasks):
                ans = v_tasks[i].get("correct", "целое число")
                row.append(str(ans))
            else:
                row.append("—")
        matrix_rows.append(row)

    matrix_t = Table(matrix_rows, colWidths=[col0_w] + [v_col_w] * num_vars)
    matrix_t.setStyle(TableStyle([
        ('FONTNAME', (0,0), (-1,-1), 'EDU'),
        ('FONTNAME', (0,0), (-1,0), 'EDU-Bold'),
        ('FONTNAME', (0,0), (0,-1), 'EDU-Bold'),
        ('FONTSIZE', (0,0), (-1,-1), 8),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#f1f5f9")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#94a3b8")),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor("#f8fafc")]),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
    ]))
    elements.append(matrix_t)
    elements.append(Spacer(1, 10))

    # Belarusian 10-point conversion scale table
    elements.append(Paragraph("Шкала перевода первичных баллов в 10-балльную отметку (ГОСТ / Минобр РБ)", STYLES["teacher_title"]))
    scale_rows = [
        ["Отметка (1–10)", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10"],
        ["Первичные баллы", "1–9", "10–19", "20–34", "35–49", "50–64", "65–74", "75–84", "85–92", "93–98", "99–100"],
    ]
    scale_col_w = (width - 90) / 10
    scale_t = Table(scale_rows, colWidths=[90] + [scale_col_w] * 10)
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
    elements.append(Spacer(1, 10))

    # Detailed Explanations
    elements.append(Paragraph("Методический разбор заданий и анализ ловушек", STYLES["teacher_title"]))
    elements.append(Spacer(1, 4))

    for v_idx, v in enumerate(variants):
        var_num = v.get("number", v_idx + 1)
        elements.append(Paragraph(f"<b>Вариант {var_num}</b>", STYLES["banner_title"]))
        elements.append(Spacer(1, 3))

        for t in (v.get("part_a", []) + v.get("part_b", [])):
            t_num = t.get("number", "")
            ans = t.get("correct", "")
            expl = t.get("explanation", "").strip()
            item_text = f"<b>{t_num}. Ответ: {ans}</b>"
            if expl:
                item_text += f" — {_safe_formula_markup(expl, asset_dir)}"
            p = Paragraph(item_text, STYLES["body"])
            elements.append(p)
            elements.append(Spacer(1, 2))
        elements.append(Spacer(1, 5))

    return elements


def normalize_input_data(data: dict[str, Any]) -> dict[str, Any]:
    """Ensures input data conforms to multi-variant structure with backward compatibility."""
    normalized = dict(data)
    normalized.setdefault("subject", "Математика")
    normalized.setdefault("title", "Пробный тест в формате стандартов ЦТ и ЦЭ 2026")
    normalized.setdefault("duration", "90 минут")
    normalized.setdefault("max_score", "100")
    normalized.setdefault("website_url", "https://edusfera.by")

    variants = normalized.get("variants") or []
    if not variants and normalized.get("questions"):
        # Wrap flat questions list into a single variant
        questions = normalized["questions"]
        part_a = [q for q in questions if not str(q.get("number","")).startswith("Б") and q.get("options")]
        part_b = [q for q in questions if q not in part_a]
        variants = [{
            "number": 1,
            "title": "Вариант 1",
            "part_a": part_a,
            "part_b": part_b
        }]
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
        topMargin=10 * mm,
        bottomMargin=14 * mm,
    )

    story: list[Flowable] = []

    subject = str(data.get("subject", "Математика"))
    title = str(data.get("title", "Пробный тест"))
    subtitle = str(data.get("topic") or "Пробный тест в формате стандартов ЦТ и ЦЭ 2026")
    duration = str(data.get("duration", "90 минут"))
    max_score = str(data.get("max_score", "100"))
    variants = data.get("variants") or []

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
            width=CONTENT_W
        )
        story.extend(header_blocks)

        # Part A section
        if part_a:
            first_num = part_a[0].get("number", "А1")
            last_num = part_a[-1].get("number", f"А{len(part_a)}")
            banner = build_section_banner(f"Часть А. Задания с выбором одного правильного ответа ({first_num}–{last_num})", CONTENT_W)
            story.append(banner)
            story.append(Spacer(1, 5))

            for task in part_a:
                story.append(build_task_flowable(task, asset_dir, CONTENT_W))

        # Part B section
        if part_b:
            first_num = part_b[0].get("number", "Б1")
            last_num = part_b[-1].get("number", f"Б{len(part_b)}")
            banner = build_section_banner(f"Часть Б. Задания с кратким числовым ответом ({first_num}–{last_num})", CONTENT_W)
            story.append(banner)
            story.append(Spacer(1, 5))

            for task in part_b:
                story.append(build_task_flowable(task, asset_dir, CONTENT_W))

        # Answer sheet for this variant
        story.append(Spacer(1, 5))
        story.append(build_variant_answer_sheet(var_title, part_a, part_b, CONTENT_W))

    # Teacher master section at the end
    master_table_raw = data.get("master_table", [])
    teacher_blocks = build_teacher_section(variants, master_table_raw, asset_dir, CONTENT_W)
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
        topMargin=10 * mm,
        bottomMargin=14 * mm,
    )
    story_student: list[Flowable] = []

    subject = str(data.get("subject", "Математика"))
    subtitle = str(data.get("topic") or "Пробный тест в формате стандартов ЦТ и ЦЭ 2026")
    duration = str(data.get("duration", "90 минут"))
    max_score = str(data.get("max_score", "100"))
    variants = data.get("variants") or []

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
            width=CONTENT_W
        )
        story_student.extend(header_blocks)

        if part_a:
            first_num = part_a[0].get("number", "А1")
            last_num = part_a[-1].get("number", f"А{len(part_a)}")
            story_student.append(build_section_banner(f"Часть А. Задания с выбором одного правильного ответа ({first_num}–{last_num})", CONTENT_W))
            story_student.append(Spacer(1, 5))
            for task in part_a:
                story_student.append(build_task_flowable(task, asset_dir, CONTENT_W))

        if part_b:
            first_num = part_b[0].get("number", "Б1")
            last_num = part_b[-1].get("number", f"Б{len(part_b)}")
            story_student.append(build_section_banner(f"Часть Б. Задания с кратким числовым ответом ({first_num}–{last_num})", CONTENT_W))
            story_student.append(Spacer(1, 5))
            for task in part_b:
                story_student.append(build_task_flowable(task, asset_dir, CONTENT_W))

        story_student.append(Spacer(1, 5))
        story_student.append(build_variant_answer_sheet(var_title, part_a, part_b, CONTENT_W))

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
    story_key = build_teacher_section(variants, master_table_raw, asset_dir, CONTENT_W)
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
