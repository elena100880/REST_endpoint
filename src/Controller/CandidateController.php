<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;

use App\Controller\JsonResponse;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

class CandidateController extends AbstractController
{
    #[Route('/candidate', name: 'candidate', methods: ['POST'])]
     public function search_candidate(Request $request): Response
    {
        /*
         * example json from Request:
         * 
         * {    
         *      "phrase": "php symfony",
         * 
         *      //optional:
         *      "date1": "1990-08-10", 
         *      "date2" : "2000-10-10",
         * 
         *      //optional:
         *      "sorting": {
         *          
         *      or:    "first_name": 2,    //that is DESC
         *      or:    "last_name": 1,     //that is ASC
         *      or:     "birth": 1,          //that is ASC
         *          
         *      }
         * }
         */


        $json = file_get_contents('php://input');
        $data = json_decode($json, $associative=true);  
        
        //TODO: some checking for valid json string, valid "phrase" (<2000 )...

        $phrase = $data['phrase'];
        if (isset($data['date1'])) $date1 = $data['date1'];
        if (isset($data['date2'])) $date2 = $data['date2'];

        if (isset($data['sorting'])) $sorting = $data['sorting'];

        function candidate_elastic_search ($phrase, $date1 = null, $date2 = null) 
        {
               /*
                * here some code for elastic search, wich returns the Array of IDs of Candidates with fields matching "phrase", 
                *  and(optionally if not null argument) - date of birth (from... to)
                *  
                *  Asume that all fields in Candidate class are already somehow indexed for the ElasticSearch.
                *  sth like this in Annotations:  @ORM\Table(name="candidate", indexes={
                                                                                @ORM\Index(name="elastic_id", columns={"candidate_id"} ),
                                                                                @ORM\Index(name="elastic_email", columns={"candidate_email"} ),
                                                                                and so on...
                                                            } )

                */
                
                $arrayOfCandidatesId = [1, 2, 3, 4, 5, 8];
                return $arrayOfCandidatesId;
        }
        
        $arrayOfCandidatesId = candidate_elastic_search($phrase, $date1, $date2);

        $entityManager = $this->getDoctrine()->getManager();
        $queryBuilder = $entityManager->createQueryBuilder()
                                        ->select('c')
                                        ->from('App\Entity\Candidate', 'c')
                                        ->setParameter('ids', $arrayOfCandidatesId)
                                        ->where('c.id in (:ids)');
        
        if (isset($sorting['first_name']) ) 
        {
            if ( $sorting['first_name'] == 1) $queryBuilder= $queryBuilder->orderBy('c.first_name', 'ASC');
            else $queryBuilder= $queryBuilder->orderBy('c.first_name', 'DESC');
        }
       
        if (isset($sorting['last_name']) ) 
        {
            if ( $sorting['last_name'] == 1) $queryBuilder= $queryBuilder->orderBy('c.last_name', 'ASC');
            else $queryBuilder= $queryBuilder->orderBy('c.last_name', 'DESC');
        }

        if (isset($sorting['birth']) ) 
        {
            if ( $sorting['birth'] == 1) $queryBuilder= $queryBuilder->orderBy('c.birth', 'ASC');
            else $queryBuilder= $queryBuilder->orderBy('c.birth', 'DESC');
        }
         
        $candidates = $queryBuilder->getQuery()->getResult();


        $elem = [ 'id' => 3, 'text' => 'test'];

        return $this->json($candidates);
    }
}
