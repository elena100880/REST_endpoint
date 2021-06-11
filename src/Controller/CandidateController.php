<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;

use App\Controller\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class CandidateController extends AbstractController
{
    #[Route('/candidate', name: 'candidate', methods: ['POST'])]
     public function search_candidate(Request $request): Response
    {
        
        $json = file_get_contents('php://input');
        $data = json_decode($json, $associative=true);

        $phrase = $data['phrase'];

        $stringDate1 = $data['date1'];
        $stringDate2 = $data['date2'];
        







        $elem = [ 'id' => 3, 'text' => 'test'];

        return $this->json($elem);
    }
}
