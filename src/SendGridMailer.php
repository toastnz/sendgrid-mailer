<?php

namespace Toast\SSSendGrid;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Mailer;

class SendGridMailer implements Mailer
{

    public function send($email) 
    {

        if (!$apiKey = Config::inst()->get(self::class, 'api_key')) {
            user_error(self::class . ' requires a SendGrid \'api_key\'. Please add it to your YML configuration.');
        }

        $sendGridEmail = new \SendGrid\Mail\Mail(); 
        $sendGridEmail->setSubject($email->getSubject());

        $from = $email->getFrom();
        if ($from && count($from)) {
            $fromEmail = array_keys($from)[0];
            $fromName = $from[$fromEmail];
            $sendGridEmail->setFrom($fromEmail, $fromName);
        }

        $replyTo = $email->getReplyTo();
        if ($replyTo && count($replyTo)) {
            $replyToEmail = array_keys($replyTo)[0];
            $replyToName = $replyTo[$replyToEmail];
            $sendGridEmail->setReplyTo($replyToEmail, $replyToName);
        }

        if (is_array($email->getTo())) {
            foreach($email->getTo() as $toEmail => $toName) {
                $sendGridEmail->addTo($toEmail, $toName);
            }
        }

        if (is_array($email->getBCC())) {
            foreach($email->getBCC() as $bccEmail => $bccName) {
                $sendGridEmail->addBcc($bccEmail, $bccName);
            }
        }

        if (is_array($email->getCC())) {
            foreach($email->getCC() as $ccEmail => $ccName) {
                $sendGridEmail->addCc($ccEmail, $ccName);
            }
        }

        foreach($email->getSwiftMessage()->getChildren() as $child) {
            if ($child instanceof \Swift_Attachment) {
                $sendGridEmail->addAttachment(base64_encode($child->getBody()), $child->getContentType(), $child->getFilename());
            }
        }

        $sendGridEmail->addContent('text/html', $email->getBody());
        $sendGridEmail->addContent('text/plain', Convert::xml2raw($email->getBody()));

        $sendgrid = new \SendGrid($apiKey);

        try {
            $response = $sendgrid->send($sendGridEmail);
            if ($response->statusCode() != 202) {
                $responseBody = json_decode($response->body(), true);
                user_error(self::class . ': ' . (isset($responseBody['errors'][0]['message']) ? $responseBody['errors'][0]['message'] : $response->body()));
            }
            return true;

        } catch (\Exception $e) {
            return false;
        }        

    }


}