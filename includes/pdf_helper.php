<?php
// includes/pdf_helper.php - PDF Invoice Generator for ABSS (Exact Fee Ledger Invoice Design)
// Matches the visual layout, typography, colors, watermark and itemization of admin/view_bill.php

require_once __DIR__ . '/../config/db.php';
if (file_exists(__DIR__ . '/fine_helper.php')) {
    require_once __DIR__ . '/fine_helper.php';
}

/**
 * Lightweight Pure-PHP PDF Generation Engine (FPDF-compatible subset)
 */
if (!class_exists('ABSS_PDF_Engine')) {
    class ABSS_PDF_Engine {
        protected $page = 0;
        protected $n = 2;
        protected $offsets = [];
        protected $buffer = '';
        protected $pages = [];
        protected $state = 0;
        protected $w = 595.28; // A4 width in pt (210mm)
        protected $h = 841.89; // A4 height in pt (297mm)
        protected $x = 28.35;  // 10mm margins
        protected $y = 28.35;
        protected $lMargin = 28.35;
        protected $tMargin = 28.35;
        protected $rMargin = 28.35;
        protected $bMargin = 28.35;
        protected $fontSizePt = 12;
        protected $fontSize = 12;
        protected $currentFont = 'Helvetica';
        protected $currentStyle = '';
        protected $drawColor = '0 0 0 RG';
        protected $fillColor = '0 0 0 rg';
        protected $textColor = '0 0 0 rg';
        protected $lineWidth = 0.567;
        protected $fonts = [
            'helvetica' => ['type' => 'core', 'name' => 'Helvetica'],
            'helvetica-bold' => ['type' => 'core', 'name' => 'Helvetica-Bold'],
            'helvetica-oblique' => ['type' => 'core', 'name' => 'Helvetica-Oblique'],
            'helvetica-boldoblique' => ['type' => 'core', 'name' => 'Helvetica-BoldOblique']
        ];

        public function __construct() {
            $this->page = 0;
            $this->state = 0;
            $this->SetFont('Helvetica', '', 10);
        }

        public function AddPage() {
            $this->page++;
            $this->pages[$this->page] = '';
            $this->state = 2;
            $this->x = $this->lMargin;
            $this->y = $this->tMargin;
        }

        public function SetMargins($left, $top, $right = null) {
            $this->lMargin = $left * 2.83465;
            $this->tMargin = $top * 2.83465;
            $this->rMargin = ($right !== null ? $right : $left) * 2.83465;
        }

        public function SetFont($family, $style = '', $size = 10) {
            $family = strtolower($family);
            if ($family == 'arial') $family = 'helvetica';
            $style = strtoupper($style);
            $fontKey = $family;
            if (strpos($style, 'B') !== false && strpos($style, 'I') !== false) {
                $fontKey .= '-boldoblique';
            } elseif (strpos($style, 'B') !== false) {
                $fontKey .= '-bold';
            } elseif (strpos($style, 'I') !== false) {
                $fontKey .= '-oblique';
            }
            $this->currentFont = $fontKey;
            $this->currentStyle = $style;
            $this->fontSizePt = $size;
            $this->fontSize = $size;
            if ($this->page > 0) {
                $this->_out(sprintf('BT /F%s %.2F Tf ET', $this->currentFont, $this->fontSizePt));
            }
        }

        public function SetTextColor($r, $g = null, $b = null) {
            if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
                $this->textColor = sprintf('%.3F g', $r / 255);
            } else {
                $this->textColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
            }
            if ($this->page > 0) $this->_out($this->textColor);
        }

        public function SetDrawColor($r, $g = null, $b = null) {
            if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
                $this->drawColor = sprintf('%.3F G', $r / 255);
            } else {
                $this->drawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
            }
            if ($this->page > 0) $this->_out($this->drawColor);
        }

        public function SetFillColor($r, $g = null, $b = null) {
            if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
                $this->fillColor = sprintf('%.3F g', $r / 255);
            } else {
                $this->fillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
            }
            if ($this->page > 0) $this->_out($this->fillColor);
        }

        public function SetLineWidth($width) {
            $this->lineWidth = $width * 2.83465;
            if ($this->page > 0) {
                $this->_out(sprintf('%.2F w', $this->lineWidth));
            }
        }

        public function Rect($x, $y, $w, $h, $style = '') {
            $x = $x * 2.83465;
            $y = $this->h - ($y * 2.83465) - ($h * 2.83465);
            $w = $w * 2.83465;
            $h = $h * 2.83465;

            $op = 'S';
            $style = strtoupper($style);
            if ($style == 'F') $op = 'f';
            elseif ($style == 'FD' || $style == 'DF') $op = 'B';

            $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x, $y, $w, $h, $op));
        }

        public function Line($x1, $y1, $x2, $y2) {
            $x1 = $x1 * 2.83465;
            $y1 = $this->h - ($y1 * 2.83465);
            $x2 = $x2 * 2.83465;
            $y2 = $this->h - ($y2 * 2.83465);
            $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2));
        }

        public function SetX($x) {
            if ($x >= 0) $this->x = $x * 2.83465;
            else $this->x = $this->w + ($x * 2.83465);
        }

        public function SetY($y) {
            $this->x = $this->lMargin;
            if ($y >= 0) $this->y = $y * 2.83465;
            else $this->y = $this->h + ($y * 2.83465);
        }

        public function SetXY($x, $y) {
            $this->SetY($y);
            $this->SetX($x);
        }

        public function GetY() {
            return $this->y / 2.83465;
        }

        public function GetX() {
            return $this->x / 2.83465;
        }

        public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false) {
            $w = $w * 2.83465;
            $h = $h * 2.83465;
            $k = 2.83465;

            if ($this->y + $h > $this->h - $this->bMargin && $h > 0) {
                $this->AddPage();
            }

            $s = '';
            if ($fill || $border == 1) {
                $op = '';
                if ($fill) $op = ($border == 1) ? 'B' : 'f';
                elseif ($border == 1) $op = 'S';
                $y_pdf = $this->h - $this->y - $h;
                $s .= sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x, $y_pdf, $w, $h, $op);
            }

            if (is_string($border)) {
                $x = $this->x;
                $y_top = $this->h - $this->y;
                $y_bot = $this->h - $this->y - $h;
                if (strpos($border, 'L') !== false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x, $y_top, $x, $y_bot);
                if (strpos($border, 'T') !== false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x, $y_top, $x + $w, $y_top);
                if (strpos($border, 'R') !== false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x + $w, $y_top, $x + $w, $y_bot);
                if (strpos($border, 'B') !== false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x, $y_bot, $x + $w, $y_bot);
            }

            if ($txt !== '') {
                $cleanTxt = $this->_escapeText($txt);
                $dx = 4;
                if ($align == 'R') {
                    $txtW = $this->GetStringWidth($txt) * $k;
                    $dx = max(2, $w - $txtW - 4);
                } elseif ($align == 'C') {
                    $txtW = $this->GetStringWidth($txt) * $k;
                    $dx = max(2, ($w - $txtW) / 2);
                }

                $y_txt = $this->h - $this->y - ($h * 0.5) - ($this->fontSizePt * 0.3);
                $s .= sprintf('BT /F%s %.2F Tf %s %.2F %.2F Td (%s) Tj ET', $this->currentFont, $this->fontSizePt, $this->textColor, $this->x + $dx, $y_txt, $cleanTxt);
            }

            if ($s) $this->_out($s);

            if ($ln > 0) {
                $this->y += $h;
                $this->x = $this->lMargin;
            } else {
                $this->x += $w;
            }
        }

        public function Watermark($text = 'UNPAID INVOICE') {
            $txt = $this->_escapeText($text);
            $cx = $this->w / 2;
            $cy = $this->h / 2;
            $angle = 35 * (M_PI / 180);
            $cos = cos($angle);
            $sin = sin($angle);

            // Watermark in subtle faint red
            $s = sprintf('q 0.95 0.88 0.88 rg BT /Fhelvetica-bold 46 Tf %.4F %.4F %.4F %.4F %.2F %.2F Tm (%s) Tj ET Q',
                $cos, $sin, -$sin, $cos, $cx - 160, $cy - 60, $txt
            );
            $this->_out($s);
        }

        public function GetStringWidth($s) {
            $len = strlen((string)$s);
            return ($len * $this->fontSizePt * 0.52) / 2.83465;
        }

        protected function _escapeText($s) {
            $s = str_replace(["₹", "₹ ", "&#8377;"], ["Rs. ", "Rs. ", "Rs. "], (string)$s);
            $s = str_replace(["“", "”", "‘", "’", "–", "—", "•"], ['"', '"', "'", "'", "-", "-", "*"], $s);
            $s = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
            $s = str_replace('\\', '\\\\', $s);
            $s = str_replace('(', '\\(', $s);
            $s = str_replace(')', '\\)', $s);
            return $s;
        }

        protected function _out($s) {
            if ($this->state == 2) {
                $this->pages[$this->page] .= $s . "\n";
            } else {
                $this->buffer .= $s . "\n";
            }
        }

        public function Output($dest = 'S', $name = 'document.pdf') {
            $this->_enddoc();

            if ($dest == 'I' || $dest == 'D') {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/pdf');
                header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                header('Pragma: public');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                $disposition = ($dest == 'D') ? 'attachment' : 'inline';
                header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
                header('Content-Length: ' . strlen($this->buffer));
                echo $this->buffer;
                exit;
            }
            return $this->buffer;
        }

        protected function _enddoc() {
            $this->state = 1;
            // 1. Header
            $this->buffer = "%PDF-1.4\n";
            $this->offsets[1] = strlen($this->buffer);
            $this->buffer .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

            // 2. Pages object
            $kids = '';
            for ($i = 1; $i <= $this->page; $i++) {
                $kids .= (2 + $i) . " 0 R ";
            }
            $this->offsets[2] = strlen($this->buffer);
            $this->buffer .= "2 0 obj\n<< /Type /Pages /Kids [" . trim($kids) . "] /Count " . $this->page . " >>\nendobj\n";

            // 3. Page objects
            $contentObjStart = 2 + $this->page;
            for ($i = 1; $i <= $this->page; $i++) {
                $pObj = 2 + $i;
                $cObj = $contentObjStart + $i;
                $this->offsets[$pObj] = strlen($this->buffer);
                $this->buffer .= "$pObj 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . sprintf('%.2F %.2F', $this->w, $this->h) . "] /Resources << /Font << ";
                foreach ($this->fonts as $k => $f) {
                    $this->buffer .= "/F$k << /Type /Font /Subtype /Type1 /BaseFont /" . $f['name'] . " /Encoding /WinAnsiEncoding >> ";
                }
                $this->buffer .= ">> >> /Contents $cObj 0 R >>\nendobj\n";
            }

            // 4. Content streams
            for ($i = 1; $i <= $this->page; $i++) {
                $cObj = $contentObjStart + $i;
                $stream = $this->pages[$i];
                $this->offsets[$cObj] = strlen($this->buffer);
                $this->buffer .= "$cObj 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n";
            }

            // Cross-Reference Table
            $startxref = strlen($this->buffer);
            $totalObj = 2 + ($this->page * 2);
            $this->buffer .= "xref\n0 " . ($totalObj + 1) . "\n0000000000 65535 f \n";
            for ($i = 1; $i <= $totalObj; $i++) {
                $this->buffer .= sprintf("%010d 00000 n \n", $this->offsets[$i] ?? 0);
            }

            $this->buffer .= "trailer\n<< /Size " . ($totalObj + 1) . " /Root 1 0 R >>\nstartxref\n$startxref\n%%EOF\n";
        }
    }
}

/**
 * Amount in Words converter helper (exact match with view_bill.php)
 */
if (!function_exists('abss_amount_to_words_pdf')) {
    function abss_amount_to_words_pdf($number) {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        );
        $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
        while( $i < $digits_length ) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter].$plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10].' '.$digits[$counter].$plural.' '.$hundred;
            } else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? "." . ($words[(int)floor($decimal / 10)] . " " . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . 'Rupees ' : '') . ($paise ? 'and ' . $paise : '') . 'Only';
    }
}

/**
 * Generates Fee Ledger Invoice PDF (Exact same structure & style as admin/view_bill.php)
 *
 * @param array $bill Full Bill Record (including student & parent details)
 * @param array $settings School Settings
 * @param string $output_mode 'S' for binary string, 'I' for inline, 'D' for download
 * @return string|void
 */
function render_fee_ledger_invoice_pdf($bill, $settings = null, $output_mode = 'S') {
    if (!$settings) {
        $settings = function_exists('getAllSettings') ? getAllSettings() : [];
    }

    $school_name = $settings['school_name'] ?? 'Awasiya Bal Shikshan Sansthan';
    $school_address = $settings['address'] ?? 'Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206';
    $school_phone = $settings['phone'] ?? '+91 9523012888';
    $school_email = $settings['email'] ?? 'abssimamganj@gmail.com';

    // Calculate dynamic late fine
    $fine_calc = function_exists('calculate_bill_fine') ? calculate_bill_fine($bill['billing_date'], $settings) : ['fine_amount' => 0.00, 'overdue_days' => 0, 'rate_per_day' => 5.00];
    $fine_amount = ($bill['status'] === 'unpaid') ? (float)$fine_calc['fine_amount'] : 0.00;
    $total_payable_amount = (float)$bill['amount'] + $fine_amount;

    $amount_in_words = abss_amount_to_words_pdf($total_payable_amount);
    $invoice_no = "ABSS-INV-" . date('Y', strtotime($bill['billing_date'])) . "-" . str_pad($bill['id'], 5, '0', STR_PAD_LEFT);
    $billed_on_date = date('d M, Y', strtotime($bill['billing_date']));

    // Initialize PDF Document
    $pdf = new ABSS_PDF_Engine();
    $pdf->SetMargins(12, 12, 12);
    $pdf->AddPage();

    // 1. Watermark: UNPAID INVOICE
    $status_watermark = strtoupper($bill['status']) . ' INVOICE';
    $pdf->Watermark($status_watermark);

    // 2. Receipt Header (School Branding on Left, Invoice Meta on Right)
    $pdf->SetXY(12, 14);
    $pdf->SetFont('Helvetica', 'B', 15);
    $pdf->SetTextColor(26, 35, 126); // #1a237e
    $pdf->Cell(115, 6, $school_name, 0, 1, 'L');

    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(85, 85, 85); // #555
    $pdf->SetX(12);
    $pdf->Cell(115, 4.5, $school_address, 0, 1, 'L');
    $pdf->SetX(12);
    $pdf->Cell(115, 4.5, 'Phone: ' . $school_phone . '  |  Email: ' . $school_email, 0, 1, 'L');

    // Right Meta Box: FEE INVOICE
    $pdf->SetXY(127, 14);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(211, 47, 47); // #d32f2f
    $pdf->Cell(71, 6, "FEE INVOICE", 0, 1, 'R');

    $pdf->SetXY(127, 20);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(51, 51, 51); // #333
    $pdf->Cell(71, 5, $invoice_no, 0, 1, 'R');

    $pdf->SetXY(127, 25);
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(102, 102, 102); // #666
    $pdf->Cell(71, 5, "Billed On: " . $billed_on_date, 0, 1, 'R');

    // Double-line Divider
    $pdf->SetDrawColor(224, 224, 224); // #e0e0e0
    $pdf->SetLineWidth(0.6);
    $pdf->Line(12, 33, 198, 33);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12, 34.5, 198, 34.5);

    // 3. Details Grid: 2 Columns (Bill To Parent | Student Details)
    $grid_y = 38;

    // Left Column: Bill To (Parent)
    $pdf->SetXY(12, $grid_y);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(26, 35, 126); // #1a237e
    $pdf->Cell(88, 5, "BILL TO (PARENT)", 'B', 1, 'L');

    $parent_name = $bill['parent_name'] ?: 'N/A';
    $parent_phone = $bill['phone'] ?: ($bill['p_phone'] ?? 'N/A');

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->SetX(12);
    $pdf->Cell(32, 5.5, "Parent Name:", 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(17, 17, 17);
    $pdf->Cell(56, 5.5, $parent_name, 0, 1, 'L');

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->SetX(12);
    $pdf->Cell(32, 5.5, "Phone:", 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(17, 17, 17);
    $pdf->Cell(56, 5.5, $parent_phone, 0, 1, 'L');

    // Right Column: Student Details
    $pdf->SetXY(110, $grid_y);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(26, 35, 126); // #1a237e
    $pdf->Cell(88, 5, "STUDENT DETAILS", 'B', 1, 'L');

    $student_name = $bill['student_name'] ?? 'Student';
    $class_name = $bill['class_admitted'] ?: 'Class 5';
    $scholar_mode = $bill['scholar_mode'] ?: 'Day Scholar';

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->SetX(110);
    $pdf->Cell(32, 5.5, "Student Name:", 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(17, 17, 17);
    $pdf->Cell(56, 5.5, $student_name, 0, 1, 'L');

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->SetX(110);
    $pdf->Cell(32, 5.5, "Class:", 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(17, 17, 17);
    $pdf->Cell(56, 5.5, $class_name, 0, 1, 'L');

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(102, 102, 102);
    $pdf->SetX(110);
    $pdf->Cell(32, 5.5, "Scholar Mode:", 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(37, 99, 235); // #2563eb
    $pdf->Cell(56, 5.5, $scholar_mode, 0, 1, 'L');

    // 4. Itemized Fee Table (Exact Header: #feeef2 bg, #d32f2f border & text)
    $table_y = 62;
    $pdf->SetXY(12, $table_y);
    $pdf->SetFillColor(254, 238, 242); // #feeef2
    $pdf->SetDrawColor(211, 47, 47);   // #d32f2f
    $pdf->SetTextColor(211, 47, 47);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetLineWidth(0.5);

    $pdf->Cell(14, 7, "S.NO", 'TB', 0, 'C', true);
    $pdf->Cell(96, 7, "FEE DESCRIPTION", 'TB', 0, 'L', true);
    $pdf->Cell(40, 7, "BILL MONTH", 'TB', 0, 'L', true);
    $pdf->Cell(36, 7, "AMOUNT DUE", 'TB', 1, 'R', true);

    $remarks = explode('|', $bill['remark'] ? $bill['remark'] : 'Tuition Fee');
    $sno = 1;
    $curr_y = $table_y + 7;

    $pdf->SetDrawColor(226, 232, 240); // #e2e8f0
    $pdf->SetLineWidth(0.3);

    foreach ($remarks as $rem) {
        $rem = trim($rem);
        if (strpos($rem, 'Auto-generated Bill.') !== false) {
            $rem = trim(str_replace('Auto-generated Bill.', '', $rem));
        }
        if (empty($rem)) continue;

        $is_payment_row = (strpos(strtolower($rem), 'payment received') !== false || strpos($rem, '-₹') !== false);
        $item_desc = $rem;
        $item_month = $bill['month_for'];
        $item_amt = '';

        if ($is_payment_row) {
            if (preg_match('/\(-?\s*[₹Rs\.]*\s*([0-9\.,]+)\)/i', $rem, $amt_match)) {
                $item_amt = '-Rs. ' . number_format((float)str_replace(',', '', $amt_match[1]), 2);
            } elseif (preg_match('/-?\s*[₹Rs\.]\s*([0-9\.,]+)/i', $rem, $amt_match)) {
                $item_amt = '-Rs. ' . number_format((float)str_replace(',', '', $amt_match[1]), 2);
            } else {
                $item_amt = '-Rs. 0.00';
            }
            if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $rem, $d_match)) {
                $item_month = date('d M Y', strtotime($d_match[0]));
                $item_desc = "Payment received on " . date('d M Y', strtotime($d_match[0]));
            } else {
                $item_desc = "Payment received";
            }
        } else {
            if (preg_match('/\((.*?)\)/', $rem, $m_match)) {
                $item_month = trim($m_match[1]);
                $rem = trim(str_replace($m_match[0], '', $rem));
            } elseif (preg_match('/\[(.*?)\]/', $rem, $m_match)) {
                $item_month = trim($m_match[1]);
                $rem = trim(str_replace($m_match[0], '', $rem));
            }

            if (strpos($rem, ': ₹') !== false) {
                $parts = explode(': ₹', $rem);
                $item_desc = trim($parts[0]);
                $item_amt = 'Rs. ' . trim($parts[1]);
            } elseif (strpos($rem, ':') !== false) {
                $parts = explode(':', $rem);
                $item_desc = trim($parts[0]);
                $item_amt = 'Rs. ' . trim($parts[1]);
            } else {
                $item_desc = trim($rem);
                $item_amt = 'Rs. ' . number_format($bill['amount'], 2);
            }

            if (preg_match('/₹\s*[0-9\.,]+/', $item_desc, $amt_match)) {
                $item_desc = trim(str_replace($amt_match[0], '', $item_desc));
            }
            if (empty($item_desc)) $item_desc = "Tuition Fee";
        }

        $pdf->SetXY(12, $curr_y);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Cell(14, 7, $sno++, 'B', 0, 'C');

        $pdf->SetFont('Helvetica', 'B', 8.5);
        if ($is_payment_row) {
            $pdf->SetTextColor(21, 128, 61); // Green #15803d
        } else {
            $pdf->SetTextColor(26, 35, 126); // #1a237e
        }
        $pdf->Cell(96, 7, $item_desc, 'B', 0, 'L');

        $pdf->SetFont('Helvetica', 'B', 8.5);
        if ($is_payment_row) {
            $pdf->SetTextColor(22, 101, 52); // Darker Green #166534
        } else {
            $pdf->SetTextColor(37, 99, 235); // #2563eb
        }
        $pdf->Cell(40, 7, $item_month, 'B', 0, 'L');

        $pdf->SetFont('Helvetica', 'B', 9);
        if ($is_payment_row) {
            $pdf->SetTextColor(21, 128, 61); // Green #15803d
        } else {
            $pdf->SetTextColor(183, 28, 28); // #b71c1c
        }
        $pdf->Cell(36, 7, $item_amt, 'B', 1, 'R');

        $curr_y += 7;
    }

    // Add Late Fine Row (if overdue)
    if ($fine_amount > 0) {
        $pdf->SetXY(12, $curr_y);
        $pdf->SetFillColor(255, 247, 237); // #fff7ed
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor(234, 88, 12);   // #ea580c

        $pdf->Cell(14, 7, $sno++, 'B', 0, 'C', true);
        $fine_label = "Late Fine (" . $fine_calc['overdue_days'] . " Days Overdue @ Rs." . number_format($fine_calc['rate_per_day'], 2) . "/day)";
        $pdf->Cell(96, 7, $fine_label, 'B', 0, 'L', true);
        $pdf->Cell(40, 7, $bill['month_for'], 'B', 0, 'L', true);
        $pdf->Cell(36, 7, "Rs. " . number_format($fine_amount, 2), 'B', 1, 'R', true);

        $curr_y += 7;
    }

    $total_payable = (float)$bill['amount'] + (float)$fine_amount;
    $amount_in_words = abss_amount_to_words_pdf($total_payable);

    // 5. Total Amount Due Strip
    $strip_y = $curr_y + 3;
    $pdf->SetXY(12, $strip_y);
    $pdf->SetFillColor(254, 238, 242);
    $pdf->SetDrawColor(255, 205, 210);
    $pdf->Rect(12, $strip_y, 186, 9, 'DF');

    $pdf->SetXY(16, $strip_y + 0.5);
    $pdf->SetFont('Helvetica', 'B', 9.5);
    $pdf->SetTextColor(183, 28, 28);
    $pdf->Cell(110, 8, "Total Amount Due", 0, 0, 'L');

    $pdf->SetFont('Helvetica', 'B', 11.5);
    $pdf->Cell(68, 8, "Rs. " . number_format($total_payable, 2), 0, 1, 'R');

    // 6. Amount in Words Block
    $words_y = $strip_y + 12;
    $pdf->SetDrawColor(211, 47, 47);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(12, $words_y, 12, $words_y + 6);

    $pdf->SetXY(15, $words_y + 0.5);
    $pdf->SetFont('Helvetica', 'I', 8.5);
    $pdf->SetTextColor(85, 85, 85);
    $pdf->Cell(45, 5, "Amount due in words: ", 0, 0, 'L');

    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->SetTextColor(211, 47, 47);
    $pdf->Cell(135, 5, $amount_in_words, 0, 1, 'L');

    $filename = "Invoice_" . $invoice_no . "_" . preg_replace('/[^a-zA-Z0-9]+/', '_', $student_name) . ".pdf";
    return $pdf->Output($output_mode, $filename);
}

/**
 * Generates Fee Invoice PDF for a single bill ID
 */
function generate_single_bill_pdf($bill_id, $conn, $settings = null, $output_mode = 'S') {
    if (!$settings) {
        $settings = function_exists('getAllSettings') ? getAllSettings() : [];
    }

    $stmt = $conn->prepare("
        SELECT fg.*, s.name as student_name, s.scholar_mode, s.class_admitted, p.parent_name, p.phone
        FROM fees_generated fg
        JOIN students s ON fg.student_id = s.id
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE fg.id = ?
    ");
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bill) {
        throw new Exception("Invoice not found for Bill ID: " . $bill_id);
    }

    return render_fee_ledger_invoice_pdf($bill, $settings, $output_mode);
}

/**
 * Generates Student Dues PDF using the exact Fee Ledger Invoice design
 */
function generate_student_due_pdf($student_id, $conn, $settings = null, $output_mode = 'S') {
    if (!$settings) {
        $settings = function_exists('getAllSettings') ? getAllSettings() : [];
    }

    // 1. Fetch Student Details
    $st_stmt = $conn->prepare("
        SELECT s.*, p.parent_name as p_name, p.phone as p_phone, p.email as p_email
        FROM students s
        LEFT JOIN parents p ON s.parent_id = p.id
        WHERE s.id = ?
    ");
    $st_stmt->bind_param("i", $student_id);
    $st_stmt->execute();
    $student = $st_stmt->get_result()->fetch_assoc();
    $st_stmt->close();

    if (!$student) {
        throw new Exception("Student not found for ID: " . $student_id);
    }

    // 2. Fetch all unpaid bills for this student
    $fees_stmt = $conn->prepare("
        SELECT id, month_for, amount, billing_date, status, remark, created_at
        FROM fees_generated
        WHERE student_id = ? AND status = 'unpaid'
        ORDER BY billing_date DESC
    ");
    $fees_stmt->bind_param("i", $student_id);
    $fees_stmt->execute();
    $fees_res = $fees_stmt->get_result();
    $unpaid_bills = [];
    $total_amount = 0.0;
    $all_remarks = [];
    $all_months = [];
    $latest_bill = null;

    while ($b = $fees_res->fetch_assoc()) {
        if ($latest_bill === null) {
            $latest_bill = $b;
        }
        $unpaid_bills[] = $b;
        $total_amount += (float)$b['amount'];
        if (!empty($b['remark'])) {
            $all_remarks[] = $b['remark'];
        } else {
            $all_remarks[] = "Tuition Fee: Rs." . number_format($b['amount'], 2) . " (" . $b['month_for'] . ")";
        }
        if (!in_array($b['month_for'], $all_months)) {
            $all_months[] = $b['month_for'];
        }
    }
    $fees_stmt->close();

    if (empty($unpaid_bills)) {
        // If no unpaid bills, fetch latest bill for record
        $latest_stmt = $conn->prepare("SELECT * FROM fees_generated WHERE student_id = ? ORDER BY id DESC LIMIT 1");
        $latest_stmt->bind_param("i", $student_id);
        $latest_stmt->execute();
        $latest_bill = $latest_stmt->get_result()->fetch_assoc();
        $latest_stmt->close();
        if (!$latest_bill) {
            $latest_bill = [
                'id' => 1,
                'amount' => 0.00,
                'billing_date' => date('Y-m-d'),
                'status' => 'paid',
                'month_for' => date('F Y'),
                'remark' => 'No Pending Dues'
            ];
        }
    }

    // Build consolidated bill object for the student matching fee ledger view
    $consolidated_bill = [
        'id' => $latest_bill['id'] ?? 1,
        'student_id' => $student['id'],
        'student_name' => $student['name'],
        'class_admitted' => $student['class_admitted'],
        'scholar_mode' => $student['scholar_mode'],
        'parent_name' => $student['parent_name'] ?: ($student['p_name'] ?: 'Parent / Guardian'),
        'phone' => $student['phone'] ?: ($student['p_phone'] ?: 'N/A'),
        'billing_date' => $latest_bill['billing_date'] ?? date('Y-m-d'),
        'status' => 'unpaid',
        'month_for' => !empty($all_months) ? implode(', ', array_reverse($all_months)) : date('F Y'),
        'amount' => $total_amount,
        'remark' => !empty($all_remarks) ? implode(' | ', $all_remarks) : 'Monthly Tuition Fee'
    ];

    return render_fee_ledger_invoice_pdf($consolidated_bill, $settings, $output_mode);
}
