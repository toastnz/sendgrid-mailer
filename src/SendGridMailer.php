<?php

namespace Toast\SSSendGrid;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Core\Environment;

class SendGridMailer implements Mailer
{

    public function send($email) 
    {

        if (!$apiKey = Config::inst()->get(self::class, 'api_key')) {
            if (!$apiKey = Environment::getEnv('SENDGRID_API_KEY')) {
                user_error(self::class . ' requires a SendGrid \'api_key\'. Please add it to your YML configuration or define SENDGRID_API_KEY in your .env file.');
            }
        }

        $sendGridEmail = new \SendGrid\Mail\Mail(); 
        $sendGridEmail->setSubject($email->getSubject());

        $emailsFrom = Email::getSendAllEmailsFrom();
        $from = is_array($emailsFrom) && count($emailsFrom) ? $emailsFrom : $email->getFrom();
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
        
        $emailsTo = Email::getSendAllEmailsTo();
        $to = is_array($emailsTo) && count($emailsTo) ? $emailsTo : $email->getTo();
        if (is_array($to)) {
            foreach($to as $toEmail => $toName) {
                $sendGridEmail->addTo($toEmail, $toName);
            }
        }

        $emailsBCC = Email::getBCCAllEmailsTo();
        $BCC = is_array($emailsBCC) && count($emailsBCC) ? $emailsBCC : $email->getBCC();
        if (is_array($BCC)) {
            foreach($BCC as $bccEmail => $bccName) {
                $sendGridEmail->addBcc($bccEmail, $bccName);
            }
        }

        $emailsCC = Email::getCCAllEmailsTo();
        $CC = is_array($emailsCC) && count($emailsCC) ? $emailsCC : $email->getCC();
        if (is_array($CC)) {
            foreach($CC as $ccEmail => $ccName) {
                $sendGridEmail->addCc($ccEmail, $ccName);
            }
        }

        foreach($email->getSwiftMessage()->getChildren() as $child) {
            if ($child instanceof \Swift_Attachment) {
                $sendGridEmail->addAttachment(base64_encode($child->getBody()), $child->getContentType(), $child->getFilename());
            }
        }

        $sendGridEmail->addContent('text/plain', Convert::xml2raw($email->getBody()));
        $sendGridEmail->addContent('text/html', $email->getBody());

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