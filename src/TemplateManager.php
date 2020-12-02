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


    private function computeText($text, array $data)
    {
        $matches = $this->getMatches($text, $data); //get all the matches of the type: [classeName:functionName];
        return $this->handleMatches($text, $data, $matches); // return the text with the right matches replalced with the adequate valute;
    }

    private function getMatches($text, array $data){

        $matches_found = [];
        // Finding all type of injection format: [className:functionName]
        preg_match_all('/\[(\w*):(\w*)\]/', $text, $matches_found, PREG_SET_ORDER);
        return $matches_found;

    }    

    private function handleMatches($text, array $data, array $matches){
        //foreach match we'll find in match[1] the classname and in match[2] the function name
        //Example: [quote:summary_html] match[1] : quote | match[2]: summary_html
        foreach ($matches as $match) {
            $replacementText = $this->getReplacementText($match[1], $match[2], $data);
            if ($replacementText) {
                $text = str_replace(
                    '['.$match[1] .':'.$match[2].']',
                    $replacementText,
                    $text
                );
            }
        }
        return $text;
    }

    //getReplacementText try to execute the functionName in className and return the data
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

    private function getCurrentContext($data){
        $APPLICATION_CONTEXT = ApplicationContext::getInstance();
        $quote = (array_key_exists('quote', $data) and $data['quote'] instanceof Quote) ? $data['quote'] : false;
        $user  = (array_key_exists('user', $data)  and ($data['user']  instanceof User))  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();
        return [
            'quote' => $quote,
            'user' => $user
        ];
    }
}
