
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // collect value of input field
    $link = $_POST['link'];
    if (empty($link)) {
        echo "Link is empty";
    } else {
        // scraping data from url
        require 'vendor/autoload.php';
        $httpClient = new \GuzzleHttp\Client();
        $response = $httpClient->get($link);
        $htmlString = (string) $response->getBody();

        //add this line to suppress any warnings
        libxml_use_internal_errors(true);
        $prologue = '<?xml encoding="UTF-8">';
        $HTMLDoc = new DOMDocument();
        $HTMLDoc->loadHTML($prologue . $htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($HTMLDoc);

        //detect html code
        $html = $xpath->evaluate('//html');
        if ($html->count() > 0) {

            $titles = $xpath->evaluate('//meta[@property="og:title"]/@content');
            $sub = $xpath->evaluate('//meta[@property="og:description"]/@content');
            $image = $xpath->evaluate('//meta[@property="og:image"]/@content');

            //exceptioanl website support
            $pees = $xpath->query('//div[@id="mvp-content-main"][.//p]');
            if ($pees->count() == 0) {
                $pees = $xpath->query('//div[.//p]');
            }

            $extractedTitles = [];
            foreach ($titles as $key => $title) {
                $extractedTitles[] = $title->textContent . PHP_EOL;
                break;
            }

            $extractedSub = [];
            foreach ($sub as $key => $sub) {
                $extractedSub[] = $sub->textContent . PHP_EOL;
                break;
            }

            $extractedImg = [];
            foreach ($image as $key => $image) {
                $extractedImg[] = $image->textContent . PHP_EOL;
                break;
            }

            #get the number of p children in each div
            $pchilds = [];
            foreach ($pees as $pee) {
                $childs = $pee->childElementCount;
                array_push($pchilds, $childs);
            }

            $extractedBody = [];
            #now find the div with the max number of p children
            foreach ($pees as $pee) {
                $childs = $pee->childElementCount;
                if ($childs == max($pchilds)) {
                    foreach ($pee->childNodes as $para) {
                        $check_dummy_pre = (preg_match('(© \d{4}|\b[background|border|font|list|line|margin|padding|stroke|text|vertical]+[A-Z].*?\b|\b[background|border|font|letter|line|list|margin|padding|stroke|vertical|text]+[A-Z].*?[A-Z].*?\b|list+\-\w+\-\w+|(letter|margin|padding|line|stroke|text|vertical|z|list)\-(\w+)|font\-\w+|border\-\w+-\w+|border\-\w+|background\-\w+|\w ©|\w \(c\)|border-bottom|background-attachment|background-color|background-image|background-position|background-repeat|function\(|"id":|id:|class:|"class":|"description":|CLASS:|"CLASS":|"ID"|="|lazyload|.jpg|.png|.html|stroke-width|.css|[share|Share]+ now on +\w+|social-link|(share|Share)+ on +\w+|ADVERTISEMENT|fb-share-button|requestSite|.push\()', $para->nodeValue));

                        if ($check_dummy_pre === 0) {
                            if (!empty($para->nodeValue)) {
                                $extractedBody[] = trim($para->nodeValue);
                            }
                        }
                    }
                }
            }

            $arr_sort = [];
            for ($i = 0; $i < count($extractedBody); $i++) {
                if ($extractedBody[$i] != null) {
                    $arr_sort[] = $extractedBody[$i];
                }
            }

            // detecting dot, not fullstop and storing mathch value in a array and creaing a search key array to replace later.
            $arr_detect_dot_not_fullStop = [];
            $search_arr = [];
            $replace_arr = [];
            for ($i = 0; $i < count($arr_sort); $i++) {
                $rule = (preg_match('(^(0[0-9]|1[0-9]|2[0-3])\.[0-5][0-9 ]+[am|AM|pm|PM]+|\b[etc|ETC]+\.\b|\.{3}|(?:[\w-]+\.)*([\w-]{1,63})(?:\.(?:\w{3}|\w{2}))|[A-Z]\.[A-Z]\.[A-Z]\.|[A-Z]\.[A-Z]\.|[A-Z][a-z]\.|[A-Z][a-z][a-z]\.)', $arr_sort[$i], $matches));
                if ($rule === 1) {
                    for ($j = 0; $j < count($matches); $j++) {
                        $search_arr[] = rand();
                        $replace_arr[] = $matches[$j];
                        for ($k = 0; $k < count($search_arr); $k++) {
                            $dot_replace_tmp = str_replace($replace_arr[$j], $search_arr[$k], $arr_sort[$i]);
                        }
                        $arr_detect_dot_not_fullStop[] = trim($dot_replace_tmp);
                    }
                } else {
                    $arr_detect_dot_not_fullStop[] = trim($arr_sort[$i]);
                }
            }

            //storing array of sentences to a logn string
            $logn_text = implode(" ", $arr_detect_dot_not_fullStop);

            if (strlen($logn_text) == strlen(utf8_decode($logn_text)) || strlen(utf8_decode($logn_text)) > strlen($logn_text) - strlen($logn_text) * 4 / 100) {
                //spliting every sentence by sentence breaker
                $each_sentence_in_array = preg_split('~[?!.]\K\s~', $logn_text, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            } else {
                //spliting every sentence by sentence breaker
                $each_sentence_in_array = preg_split('/\s*([^\x{3002}\x{FF01}\x{FF1F}]+[\x{3002}\x{FF01}\x{FF1F}]\s*)/u', $logn_text, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            }

            $output = [];
            for ($i = 0; $i < count($each_sentence_in_array); $i++) {
                if ($each_sentence_in_array[$i] != null) {
                    $output[] = str_replace($search_arr, $replace_arr, $each_sentence_in_array[$i]);
                }
            }

            //printing each sentence
            for ($i = 0; $i < count($output); $i++) {
                echo $output[$i] . '<br>';
            }
            
            
            

        } else {
            
            /*
                detect non-html
                $articlecontent will store article body
            */
            
            $articlecontent = file_get_contents($link);

            $split_in_newLine = preg_split("/\r\n|\n|\r/", $articlecontent);
            
            $arr_sort = [];
            for ($i = 0; $i < count($split_in_newLine); $i++) {
                if ($split_in_newLine[$i] != null) {
                    $arr_sort[] = $split_in_newLine[$i];
                }
            }
            
            
            // detecting dot, not fullstop and storing mathch value in a array and creaing a search key array to replace later.
            $arr_detect_dot_not_fullStop = [];
            $search_arr = [];
            $replace_arr = [];
            for ($i = 0; $i < count($arr_sort); $i++) {
                $rule = (preg_match('(^(0[0-9]|1[0-9]|2[0-3])\.[0-5][0-9 ]+[am|AM|pm|PM]+|\b[etc|ETC]+\.\b|\.{3}|(?:[\w-]+\.)*([\w-]{1,63})(?:\.(?:\w{3}|\w{2}))|[A-Z]\.[A-Z]\.[A-Z]\.|[A-Z]\.[A-Z]\.|[A-Z][a-z]\.|[A-Z][a-z][a-z]\.)', $arr_sort[$i], $matches));
                if ($rule === 1) {
                    for ($j = 0; $j < count($matches); $j++) {
                        $search_arr[] = rand();
                        $replace_arr[] = $matches[$j];
                        for ($k = 0; $k < count($search_arr); $k++) {
                            $dot_replace_tmp = str_replace($replace_arr[$j], $search_arr[$k], $arr_sort[$i]);
                        }
                        $arr_detect_dot_not_fullStop[] = trim($dot_replace_tmp);
                    }
                } else {
                    $arr_detect_dot_not_fullStop[] = trim($arr_sort[$i]);
                }
            }

            //storing array of sentences to a logn string
            $logn_text = implode(" ", $arr_detect_dot_not_fullStop);

            if (strlen($logn_text) == strlen(utf8_decode($logn_text)) || strlen(utf8_decode($logn_text)) > strlen($logn_text) - strlen($logn_text) * 4 / 100) {
                //spliting every sentence by sentence breaker
                $each_sentence_in_array = preg_split('~[?!.]\K\s~', $logn_text, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            } else {
                //spliting every sentence by sentence breaker
                $each_sentence_in_array = preg_split('/\s*([^\x{3002}\x{FF01}\x{FF1F}]+[\x{3002}\x{FF01}\x{FF1F}]\s*)/u', $logn_text, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            }

            $output = [];
            for ($i = 0; $i < count($each_sentence_in_array); $i++) {
                if ($each_sentence_in_array[$i] != null) {
                    $output[] = str_replace($search_arr, $replace_arr, $each_sentence_in_array[$i]);
                }
            }

            //printing each sentence
            for ($i = 0; $i < count($output); $i++) {
                echo $output[$i] . '<br>';
            }
            
        }
    }
}
?>
