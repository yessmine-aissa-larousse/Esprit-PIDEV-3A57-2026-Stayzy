<?php

namespace App\Controller;

use App\Service\ForumAiAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AiTestController extends AbstractController
{
    #[Route('/ai/test', name: 'ai_test')]
    public function test(ForumAiAssistant $ai): Response
    {
        $result = $ai->testConnection();

        return $this->json($result);
    }
}