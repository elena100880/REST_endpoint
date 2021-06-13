<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Exception\OutOfBoundsException;


use Symfony\Component\Routing\Annotation\Route;

use App\Controller\JsonResponse;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;

class CandidateController extends AbstractController
{
    #[Route('/candidate', name: 'candidate', methods: ['POST', 'GET'])]
     public function search_candidate(Request $request): Response
    {
        /*
         * example json data from Request (some script gives us below data):
         * 
         * {    
         *      "phrase": "php symfony",  // length < 2000 characters
         * 
         *      "date1": "1990-08-10",   //or 0 if not chosen
         *      "date2" : 0,
         * 
         *      "sorting": {         //  1 - ASC, 2 - DESC, 0 - without sorting by this field; Only the last field with 1or2 values defines the order;
         *                          //I asume that we can order by 3 below fields.
         *          "1": {"first_name": 3},  
         *          "2": {"last_name": 2},
         *          "3": {"tag": 2}
         *       },   
         *        
         *      //show or not tag and notes: 1 -  show, 0 - not show
         *      "tag": 1,  
         *      "notes": 0   
         * }
         */

        $json = file_get_contents('php://input');

// some VALIDATION - valid json string, valid "phrase" (<2000 ), other records validation
        //I asume, that processing the below Exceptions are at some SCRIPT's side, which sends Request and gets Response from this endpoint
        
        function valid_json($string)
        {
            json_decode($string);
            if (json_last_error() === 0) return true;
            else throw new \JsonException ('Invalid json');
        }

        function valid_date($date) {   //based on this source:https://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
            
            $dateAsObject = \DateTime::createFromFormat('Y-m-d', $date);
            if ( ($dateAsObject) and ($dateAsObject->format('Y-m-d') === $date) or $date == 0) return true;
            else throw new OutOfBoundsException ("Invalid date value");
        }


        if (valid_json($json)) $data = json_decode($json, $associative=true); ;
       
        if (!isset($data['phrase'])) throw new OutOfBoundsException ("No valid record for PHRASE");
        if (strlen($data['phrase'])> 2000 ) throw new LengthException ("Invalid length of PHRASE");
        else $phrase = $data['phrase'];
             
        if (!isset($data['date1']) or  !isset($data['date2']) ) throw new OutOfBoundsException ("No valid key for date");
        if ( valid_date($data['date1']) and valid_date($data['date2']) ) {$date1 = $data['date1']; $date2 = $data['date2'];}

        
        foreach ($data['sorting'] as $i=>$value) {
            if ( !in_array  (
                                key($data['sorting'][$i]), ["first_name","last_name", "tag"]
                            ) 
                ) throw new \RangeException ("Invalid sorting fields");
            if ( !in_array (
                                $data['sorting'][$i][key($data['sorting'][$i])], [0,1,2] 
                            ) 
                ) throw new \RangeException ("Invalid sorting values");
        }
       
        if (!isset($data['tag']) or  !isset($data['notes']) ) throw new OutOfBoundsException ("No record of TAG/NOTES");
        if ( !in_array($data['tag'], [0,1]) or !in_array($data['notes'], [0,1] ) ) throw new \RangeException ("Invalid tag/notes values");
                     

//ElasticSearch for Candidates:
        function candidate_elastic_search ($phrase, $date1, $date2) 
        {
               /*
                * here some code for elasticSearch, wich returns the Array of IDs of Candidates with fields matching "phrase", 
                *  and(optionally if not null argument) - date of birth (from... to)
                *  
                *  Asume that all fields in Candidate class are already somehow indexed for the ElasticSearch.
                *  sth like this in Annotations:  @ORM\Table(name="candidate", indexes={
                                                                                @ORM\Index(name="elastic_id", columns={"candidate_id"} ),
                                                                                @ORM\Index(name="elastic_email", columns={"candidate_email"} ),
                                                                                and so on...
                                                            } )

                */
                
                $arrayOfCandidatesId = [1,2,3,4,5,6,7,8,9,10,11,12];
                return $arrayOfCandidatesId;
        }
        $arrayOfCandidatesId = candidate_elastic_search($phrase, $date1, $date2);

//selecting Candidates from DB by ID and ordering:        
        $entityManager = $this->getDoctrine()->getManager();
        $queryBuilder = $entityManager->createQueryBuilder()
                                        ->select('c')
                                        ->from('App\Entity\Candidate', 'c')
                                        ->setParameter('ids', $arrayOfCandidatesId)
                                        ->where('c.id in (:ids)');
        foreach ($data['sorting'] as $i=>$value) {
            if ( $data['sorting'][$i][key($data['sorting'][$i])] == 1 ) $queryBuilder = $queryBuilder->addOrderBy('c.'.key($data['sorting'][$i]), 'ASC');
            if ( $data['sorting'][$i][key($data['sorting'][$i])] == 2) $queryBuilder = $queryBuilder->addOrderBy('c.'.key($data['sorting'][$i]), 'DESC');
        }
        $candidates = $queryBuilder->getQuery()->getResult(); //TODO - pagination

//creation return data:
        $returnArray=[];
        foreach($candidates as $candidate) {          //TODO - pagination  
            
            $element = [    'email' => $candidate->getEmail(),
                            'firstName' => $candidate->getFirstName(),
                            'lastName' => $candidate->getLastName()
                        ];
            if ($data['tag'] == 1) array_push($element, ['tag' => $candidate->getTag()] );
            if ($data['notes'] == 1) array_push($element, ['notes' => $candidate->getNotes()] );
            
            array_push($returnArray, $element);
        }   

        return $this->json($returnArray);       //TODO - pagination
    }
}
