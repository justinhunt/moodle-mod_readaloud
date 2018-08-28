<?php

/*

class.Diff.php

A class containing a diff implementation

Created by Kate Morley - http://iamkate.com/ - and released under the terms of
the CC0 1.0 Universal legal code:

http://creativecommons.org/publicdomain/zero/1.0/legalcode

*/

namespace mod_readaloud;

// A class containing functions for computing diffs between reading passage and audio transcript
class diff{

    // define the constants
    const MATCHED = 0;
    const UNMATCHED    = 1;


    public static function fetchWordArray($thetext){
        //lowercaseify
        $thetext=strtolower($thetext);

        //remove any html
        $thetext = strip_tags($thetext);

        //replace all line ends with spaces
        $thetext = preg_replace('#\R+#', ' ', $thetext);

        //search for alternatives definitions, and replace them
        $alternatives = self::fetchAlternativesArray($thetext);
        $thetext = $alternatives->newtext;

        //remove punctuation
        //see https://stackoverflow.com/questions/5233734/how-to-strip-punctuation-in-php
        $thetext = preg_replace("#[[:punct:]]#", "", $thetext);

        //split on spaces into words
        $textbits = explode(' ',$thetext);

        //remove any empty elements
        $textbits = array_filter($textbits, function($value) { return $value !== ''; });

        //re index array because array_filter converts array to assoc. (ie could have gone from indexes 0,1,2,3,4,5 to 0,1,3,4,5)
        $textbits = array_values($textbits);

        return $textbits;
    }

    /*
     * This function parses and replaces {{view|alternate}} strings from text passages
     * It is used to prepare the text for display, and also the text for comparison
     *
     * TO DO: For this whole alternates thing ...optimize so we only parse the passage once when its saved
     *  and store the index of a word with alternates, so we do not need to loop through the alternates array on checking
     *
     */
    public static function fetchAlternativesArray($thetext){
        $ret = new \stdClass();
        $regexp = "/\{\{(.*?)}}/";
        $matches = [];

        //This parses {{view|alternate}} substrings into the $matches array
        //eg "The time to hesitate {{banana|birdman}} is through {{basically|basik lee}}."
        // becomes ..
        /*
        Array
        (
            [0] => Array
            (
                [0] => {{banana|birdman}}
                [1] => {{basically|basik lee}}
        )

            [1] => Array
                (
                 [0] => banana|birdman
                 [1] => basically|basik lee
                )
        )
        */

        $result = preg_match_all($regexp,$thetext,$matches);
        //if no result an empty result set
        if(!$result){
            $ret->newtext = $thetext;
            $ret->fullmatches = [];
            $ret->alternatives=[];
            $ret->matchcount=0;
            return $ret;
        }

        //this will make arrays of view and alternate eg alt_pair[0]=banana alt_pair[1]=birdman
        foreach($matches[1] as $alt_pair){
            $alt_pairs[]=explode("|",$alt_pair);
        }

        //this creates a version of $thetext with only the "view" part of the alt_pair
        //eg "The time to hesitate banana is through basically.";
        $matchcount = count($matches[0]);
        $newtext = $thetext;
        for($index=0;$index<$matchcount;$index++){
            $newtext = str_replace($matches[0][$index],$alt_pairs[$index][0],$newtext);
            //if there was no delimiter in the matched part, we should just add the view as the alternative to prevent error later
            if(count($alt_pairs[$index]==1)){
                $alt_pairs[$index][1]=$alt_pairs[$index][0];
            }
        }

        //build return object
        $ret->fullmatches=$matches[0];
        $ret->alternatives=$alt_pairs;
        $ret->newtext=$newtext;
        $ret->matchcount=$matchcount;
        return $ret;
    }

    //Loop through passage, nest looping through transcript building collections of sequences (passage match records)
    //one sequence = sequence_length[length] + sequence_start(transcript)[tposition] + sequence_start(passage)[pposition]
    //we do not discriminate over length or position of sequence. All sequences are saved
    //returns array of sequences
    public static function fetchSequences($passage, $transcript)
    {
        $p_length = count($passage);
        $t_length = count($transcript);
        $sequences = array();
        $slength=0; //sequence length
        $tstart =0; //transcript sequence match search start index

        //loop through passage word by word
        for($pstart =0; $pstart < $p_length; $pstart++){
            //loop through transcript finding matches starting from current passage word
            //we step over the length of any sequences we find to begin search for next sequence
            while($slength + $tstart < $t_length) {
                $match = $passage[$slength + $pstart] == $transcript[$slength + $tstart];
                //if we have a match and the passage and transcript each have another word, we will continue
                //(ie to try to match the next word)
                if ($match &&
                    ($slength + $tstart + 1) < $t_length &&
                    ($slength + $pstart + 1) < $p_length ) {
                    //continue building sequence
                    $slength++;

                    //if no match or end of transcript/passage, close out the current sequence(if we even had one)
                } else {
                    //if we never even had a sequence we just move to next word in transcript
                    if ($slength == 0) {
                        $tstart++;
                    //if we had a sequence, we build the sequence object, store it in $sequences,
                        //step transcript index and look for next sequence
                    } else {
                        $sequence = new \stdClass();
                        $sequence->length = $slength;
                        $sequence->tposition = $tstart;
                        $sequence->pposition = $pstart;
                        $sequences[] = $sequence;
                        $tstart+= $slength;
                        $slength = 0;
                    }//end of "IF slength=0"
                }//end of "IF match"
            }//end of "WHILE Transcript Index < t_length"
            $slength=0;
            $tstart=0;
        }//end of "FOR each passage word"
        return $sequences;
    }//end of fetchSequences

    //for use with PHP usort and arrays of sequences
    //sort array so that long sequences come first.
    //if sequences are of equal length, the one whose transcript index is earlier comes first
    public static function cmp($a, $b)
    {
        if ($a->length == $b->length) {
            if($a->tposition == $b->tposition){
                return 0;
            }else{
                return ($a->tposition< $b->tposition) ? 1 : -1;
            }
        }
        return ($a->length < $b->length) ? 1 : -1;
    }

    //returns an array of "diff" results, one for each word(ie position) in passage
    //i) default all passage positions to unmatched (self::UNMATCHED)
    //ii) sort sequences by length, transcript position
    //iii) for each sequence
    //   a)- check passage match in sequence was not already matched by previous sequence (bust if so)
    //   b)- check transcript match in sequence[tpos -> tpos+length] was not already allocated to another part of passage in previous sequence
    //   c)- check passage match position and transcript position are consistent with previous sequences
    //     inconsistent example: If T position 3 was matched to P position 5, T position 4 could not match with P position 2
    public static function fetchDiffs($sequences, $passagelength){
        //i) default passage positions to unmatched and transcript position -1
        $diffs=array_fill(0, $passagelength, [self::UNMATCHED,-1]);

        //ii) sort sequences by length, transcript posn
        usort($sequences, array('\mod_readaloud\diff','cmp'));

        //record prior sequences for iii)
        $priorsequences=array();

        //iii) loop through sequences
        foreach($sequences as $sequence){
            $bust=false;
            //iii) a) check passage position not already matched
            for($p=$sequence->pposition; $p < $sequence->pposition + $sequence->length; $p++){
                if($diffs[$p][0] !=self::UNMATCHED){
                    $bust=true;
                }
            }
            if(!$bust){
                foreach($priorsequences as $priorsequence){
                    //iii) b) check transcript match was not matched elsewhere in passage
                    if($sequence->tposition >= $priorsequence->tposition &&
                        $sequence->tposition  <= $priorsequence->tposition + $priorsequence->length){
                        $bust=true;
                        break;
                    }
                    //iii) c) check passsage match and transcript match positions are consistent with prev. sequences
                    if($sequence->tposition <= $priorsequence->tposition &&
                        $sequence->pposition >= $priorsequence->pposition){
                        $bust=true;
                        break;
                    }
                    if($sequence->tposition >= $priorsequence->tposition &&
                        $sequence->pposition <= $priorsequence->pposition){
                        $bust=true;
                        break;
                    }
                }
            }
            if($bust){continue;}

            //record sequence as :
            //i) matched and
            //ii) record transcript position so we can play it back.
            //Then store sequence in prior sequences
            for($p=$sequence->pposition; $p < $sequence->pposition + $sequence->length; $p++){
                //word position in sequence ( 0 = first )
                $wordposition = $p - $sequence->pposition;
                //NB pposition starts from 1. We adjust tposition to match
                $tposition = $sequence->tposition + $wordposition + 1;
                $diffs[$p]=[self::MATCHED,$tposition];
                $priorsequences[] = $sequence;
            }
        }
        return $diffs;
    }
}

?>
