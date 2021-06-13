<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Exception\OutOfBoundsException;
//use Symfony\Component\Validator\Exception\OutOfRangeException;

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
         *      "phrase": "php symfony",  //asume, that this record must be required and its length < 2000 characters
         * 
         *      "date1": "1990-08-10",   //or 0 if not chosen
         *      "date2" : 0,
         * 
         *      "sorting": {         // I asume that sorting is made by only one field at once, 1 - ASC, 2 - DESC, 0 - without sorting by 
         *          "first_name": 2,  
         *          "last_name": 0
         *       },   
         * 
         *      //flags - show or not tag and notes:  //asume that they are required, 1 -  show, 0 - not show
         *      "tag": 1,  
         *      "notes": 0   
         *      
         * }
         */

        $json = file_get_contents('php://input');

//VALIDATION - valid json string, valid "phrase" (<2000 ), other records validation
        //I asume, that processing the below Exceptions are at some SCRIPT's side, which sends Request and gets Response from this endpoint
        
        function check_json($json)
        {
            //some code for checking if $json is a valid json data
            return true;
        }
        if (!check_json($json)) throw new Exception ('Invalid json');


        $data = json_decode($json, $associative=true); 
        
        if (!isset($data['phrase'])) throw new OutOfBoundsException ("No valid record of PHRASE");
        if (strlen($data['phrase'])> 2000 ) throw new LengthException ("Invalid length of PHRASE");
        else $phrase = $data['phrase'];
        
        function valid_date($date) {
            
            $dateAsObject = \DateTime::createFromFormat('Y-m-d', $date);
            $ttt =  $dateAsObject->format('Y-m-d');
            if ( ($dateAsObject) and ($dateAsObject->format('Y-m-d') === $date) or $date == 0) return true;
            else return false;
        }
        $r=valid_date('1-02-15' );

        if (!isset($data['date1']) or  !isset($data['date2']) ) throw new OutOfBoundsException ("No valid key for date");
        if ( valid_date($data['date1']) and valid_date($data['date2']) )
        {
            $date1 = $data['date1']; 
            $date2 = $data['date2'];
        }

        if (!isset($data['sorting']['first_name']) or !isset($data['sorting']['last_name'] ) ) throw new OutOfBoundsException ("No valid key for FIRST/LAST_NAME");
        if ( !in_array($data['sorting']['first_name'], [0,1,2]) or !in_array($data['sorting']['last_name'], [0,1,2] ) ) throw new RangeException ("Invalid sorting values");
        
        if (!isset($data['tag']) or  !isset($data['notes']) ) throw new OutOfBoundsException ("No record of TAG/NOTES");
        if ( !in_array($data['tag'], [0,1]) or !in_array($data['notes'], [0,1] ) ) throw new RangeException ("Invalid tag/notes values");
                     

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
                
                $arrayOfCandidatesId = [1, 2, 3, 4, 5, 6];
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
        if ($data['sorting']['first_name'] == 1) $queryBuilder= $queryBuilder->orderBy('c.first_name', 'ASC');
        if ($data['sorting']['first_name'] == 2) $queryBuilder= $queryBuilder->orderBy('c.first_name', 'DESC');
                
        if ($data['sorting']['last_name'] == 1) $queryBuilder= $queryBuilder->orderBy('c.last_name', 'ASC');
        if ($data['sorting']['last_name'] == 2) $queryBuilder= $queryBuilder->orderBy('c.last_name', 'DESC');
         
        else $queryBuilder= $queryBuilder->orderBy('c.id', 'ASC'); //on default - sorting by id
         
        $candidates = $queryBuilder->getQuery()->getResult();

//creation return data:
        $returnArray=[];
        foreach($candidates as $candidate) {       
            
            $element = [    'email' => $candidate->getEmail(),
                            'firstName' => $candidate->getFirstName(),
                            'lastName' => $candidate->getLastName()
                        ];
            if ($data['tag'] == 1) array_push($element, ['tag' => $candidate->getTag()] );
            if ($data['notes'] == 1) array_push($element, ['notes' => $candidate->getNotes()] );
            
            array_push($returnArray, $element);
        }   

        return $this->json($returnArray);
    }
}
