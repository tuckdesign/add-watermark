<?php

namespace tuckdesign;

class AddWatermark
{
    private $file = null;
    private $outfile = null;
    private $text = '';
    private $x = 0;
    private $y = 0;
    private $angle = 0;
    private $red = 192;
    private $green = 192;
    private $blue = 192;
    private $fontName = '';
    private $fontSize = 20;

    /**
     * @param string $file full path to pdf/image
     * @param string $text text of watermark
     * @param int $x x coordinate (if negative will be calculated from right side)
     * @param int $y y coordinate (if negative will be calculated from bottom)
     * @param int $fontSize font size
     * @param int $angle angle of watermark
     * @param string $type 'pdf' or 'img', if left empty will be detected from extension
     * @param array $color array for color in format [red, green, blue]
     *
     * @return void
     *
     * @throws \tuckdesign\AddWatermarkException
     *
     */
    public function __construct(string $file, string $text, int $x = 0, int $y = 0, int $fontSize = 20, int $angle = 0, string $type = null, array $color = null, string $fontName = 'FreeSerif')
    {
        $this->file = $file;
        $this->outfile = null;
        $this->fontName = $fontName;
        if ($color) {
            $this->red = $color[0];
            $this->green = $color[1];
            $this->blue = $color[2];
        }
        if (!is_readable($this->file)) {
            throw new AddWatermarkException('File '.$this->file.' is not readable');
        }
        $tmp = explode('.', $this->file);
        $ext = strtolower($tmp[count($tmp) - 1]);
        $this->outfile = sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;
        $this->text = $text;
        $this->x = $x;
        $this->y = $y;
        $this->fontSize = $fontSize;
        $this->angle = $angle;
        if (!$type) {
            if ($ext == 'pdf') {
                $type = 'pdf';
            } else {
                $type = 'img';
            }
        }
        switch ($type) {
            case 'pdf':
                $this->addWatermarkPDF();
                break;
            case 'img':
                $this->whatermarkIMG();
                break;
            default:
                $this->outfile = null;
                break;
        }
    }

    /**
     * @return string|null filename of new pdf/image with watermark
     */
    public function getOutputFile()
    {
        return $this->outfile;
    }

    private function addWatermarkPDF()
    {
        $pdf = new \Mpdf\Mpdf();
        $pages_count = $pdf->setSourceFile($this->file);
        for ($i = 1; $i <= $pages_count; $i++) {
            $pdf->AddPage();
            $tplIdx = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplIdx);
            $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);
            $pdf->SetFont($this->fontName, 'B', $this->fontSize);
            $pdf->SetTextColor($this->red, $this->green, $this->blue);
            $x = $this->x;
            $y = $this->y;
            if ($x < 0) {
                $x = $size['width'] + $x;
            }
            if ($y < 0) {
                $y = $size['height'] + $y;
            }
            $this->oneWatermarkPDF($pdf, $x, $y);
            $pdf->SetXY(25, 25);
        }
        $pdf->Output($this->outfile, \Mpdf\Output\Destination::FILE);
    }

    private function oneWatermarkPDF($pdf, $x, $y)
    {
        $angle = $this->angle * M_PI / 180;
        $c = cos($angle);
        $s = sin($angle);
        $cx = $x * 1;
        $cy = (300 - $y) * 1;
        $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        $pdf->Text($x, $y, $this->text);
        $pdf->_out('Q');
    }

    private function whatermarkIMG()
    {
        $mpdfConfig = new Mpdf\Config\ConfigVariables();
        $mpdfDefaults = $mpdfConfig->getDefaults();
        $fontFileFinder = new \Mpdf\Fonts\FontFileFinder($mpdfDefaults['fontDir']);
        try {
            $fontFile = $fontFileFinder->findFontFile($this->fontName . 'Bold.ttf');
        } catch (\Mpdf\MpdfException $e) {
            try {
                $fontFile = $fontFileFinder->findFontFile($this->fontName . '.ttf');
            } catch (\Mpdf\MpdfException $e) {
                throw new AddWatermarkException($e->getMessage());
            }
        }
        try {
            list($width, $height, $type) = getimagesize($this->file);
            $scale = \Mpdf\Mpdf::SCALE;
            $x = $this->x * $scale;
            $y = $this->y * $scale;
            if ($x < 0) {
                $x = $width + $x;
            }
            if ($y < 0) {
                $y = $height + $y;
            }
            $imageType = '';
            switch ($type) {
                case IMAGETYPE_AVIF:
                    $imageType = 'avif';
                    break;
                case IMAGETYPE_BMP:
                    $imageType = 'bmp';
                    break;
                case IMAGETYPE_GIF:
                    $imageType = 'gif';
                    break;
                case IMAGETYPE_JPEG:
                    $imageType = 'jpeg';
                    break;
                case IMAGETYPE_PNG:
                    $imageType = 'png';
                    break;
                case IMAGETYPE_WBMP:
                    $imageType = 'wbmp';
                    break;
                case IMAGETYPE_WEBP:
                    $imageType = 'webp';
                    break;
                default:
                    throw new AddWatermarkException("Could not find image type for ".$this->file);;
                    break;
            }
            $createImage = 'imagecreatefrom' . $imageType;
            $saveImage = 'image' . $imageType;
            if (!function_exists($createImage) || !function_exists($saveImage)) {
                throw new AddWatermarkException("Could not find image type for ".$this->file);;
            }
            $targetLayer = $createImage($this->file);
            $newImage = imagecreatetruecolor($width, $height);
            imagecopyresampled($newImage, $targetLayer, 0, 0, 0, 0, $width, $height, $width, $height);
            $watermarkColor = imagecolorallocate($newImage, $this->red, $this->green, $this->blue);
            imagettftext($newImage, $this->fontSize, $this->angle, $x, $y, $watermarkColor, $fontFile, $this->text);
            $saveImage($newImage, $this->outfile);
        } catch (\Exception $e) {
            throw new AddWatermarkException($e->getMessage());
        }
    }
}
