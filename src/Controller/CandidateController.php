<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Config\Definition\Exception\Exception;

use Symfony\Component\Routing\Annotation\Route;

use App\Controller\JsonResponse;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;



class CandidateController extends AbstractController
{
    #[Route('/candidate', name: 'candidate', methods: ['POST'])]
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
         *      "sorting": {         //  1 - ASC, 2 - DESC, 0 - without sorting by this field; EARLIER field is more SENIOR in ordering;
         *                          //I asume that we can order by 3 below fields.
         *          "1": {"first_name": "ASC"},  
         *          "2": {"last_name": "DESC"},
         *          "3": {"tag": 0}
         *       },   
         *        
         *      //show or not tag and notes: 1 -  show, 0 - not show
         *      "tag": 1,  
         *      "notes": 0,
         * 
         *      "page": 3,          //count from 1
         *      "elements": 3  //max 20
         * }
         */

        $json = file_get_contents('php://input');

// some VALIDATION - valid json string, valid "phrase" (<2000 ), other records validation
        //I asume, that processing the below Exceptions are at some SCRIPT's side, which sends Request and gets Response from this endpoint
        
        function valid_json($string)  //https://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php
        {
            json_decode($string);
            return json_last_error() === 0;
        }

        function valid_date($date) {   //based on this source:https://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
            
            $dateAsObject = \DateTime::createFromFormat('Y-m-d', $date);
            return ( ($dateAsObject) and ($dateAsObject->format('Y-m-d') === $date) or $date == 0  );
            //else throw new OutOfBoundsException ("Invalid date value");
        }

    try 
    {
        $data = (valid_json($json)) ? json_decode($json, true) : throw new Exception('Invalid json'); 
       
        if (!isset($data['phrase'])) throw new Exception('Invalid key for PHRASE');
        $phrase = (strlen($data['phrase']) < 2000 ) ? $data['phrase'] : throw new Exception ("Invalid length of PHRASE");
                     
        if (!isset($data['date1']) or  !isset($data['date2']) ) throw new Exception ("Ivalid key for date");
        if ( valid_date($data['date1']) and valid_date($data['date2']) ) {$date1 = $data['date1']; $date2 = $data['date2'];}
        else throw new Exception ("Invalid value of date");
        
        if (!isset($data['sorting']) ) throw new Exception ("Ivalid key for sorting arrays");
        foreach ($data['sorting'] as $i=>$value) {
            if ( !in_array  (  key($data['sorting'][$i]), ["first_name","last_name", "tag"] ) 
                ) throw new Exception ("Invalid sorting fields");
            if ( !in_array (  $data['sorting'][$i][key($data['sorting'][$i])], [0,1,2]  ) 
                ) throw new Exception ("Invalid sorting values");
        }
       
        if (!isset($data['tag']) or  !isset($data['notes']) ) throw new Exception ("Invalid key for TAG/NOTES");
        if ( !in_array($data['tag'], [0,1]) or !in_array($data['notes'], [0,1] ) ) throw new Exception ("Invalid tag/notes value");

        if (!isset($data['page']) or  !isset($data['elements']) ) throw new Exception ("Invalid key for pagination");
        if ( !is_int($data['page']) or $data['page'] == 0) throw new Exception ("Invalid value of PAGE");
        else $page = $data['page'];

        if ( !is_int($data['elements']) or $data['elements'] > 20  or $data['elements'] == 0) throw new Exception ("Invalid value of ELEMENTS");
        else $elementsPerPage = $data['elements'];

    }
    catch (Exception $e) {
        return new Response ($e->getMessage(), Response::HTTP_BAD_REQUEST);
    }

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

//selecting from Candidates by ID and ordering:        
        $order = [];
        foreach ($data['sorting'] as $i=>$value) {
            if ( $data['sorting'][$i][key(  $data['sorting'][$i] )] == 1 ) $order[$i] = 'c.'.key($data['sorting'][$i]).' ASC';
            if ( $data['sorting'][$i][key(  $data['sorting'][$i] )] == 2 ) $order[$i] = 'c.'.key($data['sorting'][$i]).' DESC';
        }
        $orderBy = (empty($order) ) ? '' : 'ORDER BY '.implode(', ', $order);

        $dql = "SELECT c
                FROM App\Entity\Candidate c 
                WHERE c.id IN (:ids) 
                $orderBy";
        $entityManager = $this->getDoctrine()->getManager();        
        $DQLquery = $entityManager  ->createQuery($dql)
                                    ->setParameter('ids', $arrayOfCandidatesId)

                                    ->setFirstResult($page * $elementsPerPage  - $elementsPerPage)
                                    ->setMaxResults($elementsPerPage);

       
        $candidatesPages = new Paginator($DQLquery);  
        $totalElements = count($candidatesPages);  

        $returnData=[];
        foreach($candidatesPages as $candidate) {          
            
            $element = [    'email' => $candidate->getEmail(),
                            'firstName' => $candidate->getFirstName(),
                            'lastName' => $candidate->getLastName()
                        ];
            if ($data['tag'] == 1) array_push($element, ['tag' => $candidate->getTag()] );
            if ($data['notes'] == 1) array_push($element, ['notes' => $candidate->getNotes()] );
            
            array_push($returnData, $element);
        }   
        $returnResponse = ["data" => $returnData, "total" => $totalElements];
                   
        return $this->json($returnResponse);       
    }
}
