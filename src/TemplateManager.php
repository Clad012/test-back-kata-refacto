<?php

class TemplateManager
{
    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }
    private function getMatches($text, array $data){

        $matches_found = [];
        // Finding all type of injection format: [className:functionName]
        preg_match_all('/\[(\w*):(\w*)\]/', $text, $matches_found, PREG_SET_ORDER);
        return $matches_found;

    }    
    private function getCurrentContext($data){
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();
        $quote = (array_key_exists('quote', $data) and $data['quote'] instanceof Quote) ? $data['quote'] : false;
        $user  = (array_key_exists('user', $data)  and ($data['user']  instanceof User))  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();
        return [
            'quote' => $quote,
            'user' => $user
        ];
    }
    private function getReplacementText($className, $functionName, $data)
    {
        $context = $this->getCurrentContext($data);
        $contextObject = null;
        // We check if the class exist in the context array of objects and not null
        if (array_key_exists($className, $context) and $context[$className]) {
            $contextObject = $context[$className];
        } else {
            return false;
        }
         // is_callable test if the (functionName) can be called inside the contextObject object
        if (is_callable(array($contextObject, $functionName), false, $callable_name)) {
            return $contextObject->$functionName();
        } else {
            dd($functionName." Can't be called");
            return false;
        }
        return false;
    }

    private function handleMatches($text, array $data, array $matches){

        //foreach match we'll find in match[1] the classname and in match[2] the function name
        //Example: [quote:summary_html] match[1] : quote | match[2]: summary_html
        foreach ($matches as $match) {
            $replacementText = $this->getReplacementText($match[1], $match[2], $data);
            if ($replacementText) {
                $text = str_replace(
                    '[' . $match[1] . ':' . $match[2] .']',
                    $replacementText,
                    $text
                );
            }
        }
        return $text;
    }


    private function computeText($text, array $data)
    {
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        $quote = (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;

        if ($quote)
        {
            $_quoteFromRepository = QuoteRepository::getInstance()->getById($quote->id);
            $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
            $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);

            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            }

            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[quote:summary_html]',
                        Quote::renderHtml($_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[quote:summary]',
                        Quote::renderText($_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$destinationOfQuote->countryName,$text);
        }

        if (isset($destination))
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        else
            $text = str_replace('[quote:destination_link]', '', $text);

        /*
         * USER
         * [user:*]
         */
        $_user  = (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();
        if($_user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($_user->firstname)), $text);
        }

        return $text;
    }
}
