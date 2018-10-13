<?php

/**
 * Differencing class for Poodll Readaloud
 *
 * A class containing functions for computing diffs between reading passage and audio transcript
 * This mght be hard to follow, but its documented as well as I could
 *
 * @package    mod_readaloud
 * @copyright  2018 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_readaloud;

//
class diff{

    // define the constants
    const MATCHED = 0;
    const UNMATCHED = 1;


    /*
 * Split passage of text into an array of words
 *
 */
    public static function fetchWordArray($thetext){

        //tidy up the text so its just lower case words seperated by spaces
        $thetext = self::cleanText($thetext);

        //split on spaces into words
        $textbits = explode(' ',$thetext);

        //remove any empty elements
        $textbits = array_filter($textbits, function($value) { return $value !== ''; });

        //re index array because array_filter converts array to assoc. (ie could have gone from indexes 0,1,2,3,4,5 to 0,1,3,4,5)
        $textbits = array_values($textbits);

        return $textbits;
    }

 /*
 * Clean word of things that might prevent a match
  * i) lowercase it
  * ii) remove html characters
  * iii) replace any line ends with spaces (so we can "split" later)
  * iv) remove punctuation
 *
 */
    public static function cleanText($thetext){
        //lowercaseify
        $thetext=strtolower($thetext);

        //remove any html
        $thetext = strip_tags($thetext);

        //replace all line ends with spaces
        $thetext = preg_replace('#\R+#', ' ', $thetext);

        //remove punctuation
        //see https://stackoverflow.com/questions/5233734/how-to-strip-punctuation-in-php
        $thetext = preg_replace("#[[:punct:]]#", "", $thetext);

        return $thetext;
    }

/*
 * This function parses and replaces {{view|alternate}} strings from text passages
 * It is used to prepare for comparison
 *
 * TO DO: For this whole alternates thing ...optimize so we only parse the passage once when its saved
 *  and store the index of a word with alternates, so we do not need to loop through the alternates array on checking
 *
 */
public static function fetchAlternativesArray($thealternates)
{
    //return empty if input data is useless
    if(trim($thealternates)==''){
        return [];
    }
    //regexp from https://stackoverflow.com/questions/7058168/explode-textarea-php-at-new-lines
    $lines = preg_split('/\r\n|[\r\n]/', $thealternates);
    $alternatives = [];

    foreach($lines as $line){
        if(!empty(trim($line))) {
            $set = explode('|', $line);
            switch(count($set)){
                case 0:
                case 1:
                    continue;
                case 2:
                default:
                    //clean each word in set
                    $words= [];
                    foreach($set as $word){
                        $word = trim($word);
                        if($word !='*') {
                            $word = self::cleanText($word);
                        }
                        $words[]=$word;
                    }
                    $alternatives[] = $words;
            }
        }
    }
    return $alternatives;
}


    //Loop through passage, nest looping through transcript building collections of sequences (passage match records)
    //one sequence = sequence_length[length] + sequence_start(transcript)[tposition] + sequence_start(passage)[pposition]
    //we do not discriminate over length or position of sequence at this stage. All sequences are saved

    //NB The sequence length should be the same in the passage and transcript (because they "matched")
    //But we attempted to have "multiple word alternatives" which could mean that the match length in the transcript
    // would differ from the match length in the passage
    //eg 1989 -> nineteen eighty nine.
    // BUT we cancelled this feature because the code became more complex than wanted to maintain,
    // however still kept the transcript sequence length and passage sequence length code in place in this function
    // so we could have another go at this if needed
    //
    //returns array of sequences
    public static function fetchSequences($passage, $transcript, $alternatives)
    {
        $p_length = count($passage);
        $t_length = count($transcript);
        $sequences = array();
        $t_slength=0; //sequence length (in the transcript)
        $p_slength=0; //sequence length (in the passage)
        $tstart =0; //transcript sequence match search start index


        //loop through passage word by word
        for($pstart =0; $pstart < $p_length; $pstart++){
            //loop through transcript finding matches starting from current passage word
            //we step over the length of any sequences we have already found to begin search for next sequence
            while($t_slength + $tstart < $t_length &&
                $p_slength + $pstart < $p_length
            ) {
                //check for a direct match
                $passageword= $passage[$p_slength + $pstart];
                $transcriptword =$transcript[$t_slength + $tstart];
                $match = $passageword == $transcriptword;
                $t_matchlength=1;

                //if no direct match is there an alternates match
                if(!$match && $alternatives){
                    $matchlength = self::check_alternatives_for_match($passageword,
                        $transcriptword,
                        $alternatives);
                    if($matchlength){
                        $match= true;
                        $t_matchlength = $matchlength;

                    }
                }//end of if no direct match

                //if we have a match and the passage and transcript each have another word, we will continue
                //(ie to try to match the next word)
                if ($match &&
                    ($t_slength + $tstart + $t_matchlength) < $t_length &&
                    ($p_slength + $pstart + 1) < $p_length ) {
                    //continue building sequence
                    $p_slength++;
                    $t_slength+= $t_matchlength;

                    //else: no match or end of transcript/passage,
                } else {
                    //if we have a match here, then its the last word of passage or transcript...
                    //we build our sequence object, store it in $sequences, and return
                     if($match){
                         $p_slength++;
                         $t_slength+= $t_matchlength;
                         $sequence = new \stdClass();
                         $sequence->length = $p_slength;
                         $sequence->tlength = $t_slength;
                         $sequence->tposition = $tstart;
                         $sequence->pposition = $pstart;
                         $sequences[] = $sequence;
                         //we bump tstart, which will end this loop
                         $tstart+= $t_slength;


                         //if we never even had a sequence we just move to next word in transcript
                    }elseif ($p_slength == 0) {
                        $tstart++;

                    //if we had a sequence but this is not a match, we build the sequence object, store it in $sequences,
                     //step transcript index and look for next sequence
                    } else {
                        $sequence = new \stdClass();
                        $sequence->length = $p_slength;
                        $sequence->tlength = $t_slength;
                        $sequence->tposition = $tstart;
                        $sequence->pposition = $pstart;
                        $sequences[] = $sequence;

                        //re init transcript loop variables for the next pass
                        $tstart+= $t_slength;
                        $p_slength = 0;
                        $t_slength = 0;
                    }//end of "IF slength=0"
                }//end of "IF match"
            }//end of "WHILE Transcript Index < t_length"
            //reset transcript loop variables for each pass of passageword loop
            $slength=0;
            $tstart=0;
            $altmatchcount = 0;
        }//end of "FOR each passage word"
        return $sequences;
    }//end of fetchSequences

    /*
     * This will run through the list of alternatives for a given passageword, and if any match the transcript,
     * will return the length of the match. Anything greater than 0 is a full match.
     * We just look for single word matches currently, but still return length of match (ie its always 1)
     */
    public static function check_alternatives_for_match($passageword,$transcriptword,$alternatives){
            $match =false;
            $matchlength=0;

            //loop through all alternatives
            //and then through each alternative->wordset

            foreach($alternatives as $alternateset){
                if($alternateset[0]==$passageword){
                    for($setindex =1;$setindex<count($alternateset);$setindex++) {
                        if ($alternateset[$setindex] == $transcriptword || $alternateset[$setindex] == '*') {
                            $match = true;
                            $matchlength = 1;
                            break;
                        }
                    }
                }//end of if alternatesset[0]
                if($match==true){break;}
            }//end of for each alternatives
        //we return the matchlength
        return $matchlength;
    }

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
    //ii) sort sequences by length(longer sorts higher), transcript position (earlier sorts higher)
    //iii) for each sequence
    //   a)- check passage match in sequence was not already matched by previous sequence (bust if so)
    //   b)- check transcript match in sequence[tpos -> tpos+length] was not already allocated to another part of passage in previous sequence
    //   c)- check passage match position and transcript position are consistent with previous sequences
    //     inconsistent example: If T position 3 was matched to P position 5, T position 4 could not match with P position 2
    //
    //NB aborted supporting "multiple word alternatives" at this point. We know the sequence length in transcript
    //but we can not add a valid tposition for a pposition in the final diff array when the pposition occurs
    // after an alternate match in the same sequence. At that point gave up ... for now. Justin 2018/08
    public static function fetchDiffs($sequences, $passagelength){
        //i) default passage positions to unmatched and transcript position -1
        $diffs=array_fill(0, $passagelength, [self::UNMATCHED,-1]);

        //ii) sort sequences by length, transcript posn
        //long sequences sort higher, and are placed in the diff array first
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
