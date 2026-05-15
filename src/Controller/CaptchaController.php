<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaptchaController extends AbstractController
{
    private const CHARS  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const LENGTH = 6;

    #[Route('/captcha/image', name: 'captcha_image')]
    public function image(Request $request): Response
    {
        // Generate a random code and store it in the session
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }
        $request->getSession()->set('captcha_code', strtoupper($code));

        // Image dimensions
        $width  = 160;
        $height = 50;
        $img    = imagecreatetruecolor($width, $height);

        // Colors
        $bg       = imagecolorallocate($img, 240, 244, 248);
        $textCol  = imagecolorallocate($img, 30,  60,  120);
        $noiseCol = imagecolorallocate($img, 180, 190, 200);

        imagefill($img, 0, 0, $bg);

        // Noise lines
        for ($i = 0; $i < 6; $i++) {
            imageline($img,
                random_int(0, $width), random_int(0, $height),
                random_int(0, $width), random_int(0, $height),
                $noiseCol
            );
        }

        // Noise dots
        for ($i = 0; $i < 80; $i++) {
            imagesetpixel($img, random_int(0, $width), random_int(0, $height), $noiseCol);
        }

        // Draw each character with slight rotation effect (shift y)
        $x = 12;
        foreach (str_split($code) as $char) {
            $y     = random_int(10, 20);
            $size  = random_int(16, 22);
            $angle = random_int(-15, 15);
            $font  = $this->getParameter('kernel.project_dir') . '/public/frontOffice/fonts/captcha.ttf';

            if (file_exists($font)) {
                imagettftext($img, $size, $angle, $x, $y + $size, $textCol, $font, $char);
                $x += 22;
            } else {
                // Fallback: built-in font (no TTF needed)
                imagestring($img, 5, $x, 15, $char, $textCol);
                $x += 22;
            }
        }

        // Output PNG
        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);

        return new Response($imageData, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }
}
