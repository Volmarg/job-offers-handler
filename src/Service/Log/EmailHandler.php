<?php

namespace JobSearcher\Service\Log;

use Monolog\Formatter\HtmlFormatter;
use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\MailerHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\ParameterizedHeader;

/**
 * Overwrites the default email handler used for symfony_mailer
 */
class EmailHandler extends MailerHandler
{
    /**
     * @param MailerInterface $mailer
     * @param callable|Email  $messageTemplate
     * @param int|string      $level
     * @param bool            $bubble
     * @param HtmlFormatter   $htmlFormatter
     */
    public function __construct(
        MailerInterface $mailer,
        callable|Email  $messageTemplate,
        int|string      $level = Logger::DEBUG,
        bool            $bubble = true,
        HtmlFormatter   $htmlFormatter
    ) {
        parent::__construct($mailer, $messageTemplate, $level, $bubble);
        $this->formatter = $htmlFormatter;
    }

    /**
     * @param string $content
     * @param array  $records
     *
     * @return Email
     */
    protected function buildMessage(string $content, array $records): Email
    {
        $email = parent::buildMessage($content, $records);
        $email->getHeaders()->add($this->getTagsHeader());

        return $email;
    }

    /**
     * {@link https://mailpit.axllent.org/docs/usage/tagging/}
     *
     * @return ParameterizedHeader
     */
    private function getTagsHeader(): ParameterizedHeader
    {
        $tags = [
            "date-" . (new \DateTime())->format("Y-m-d"),
        ];

        return new ParameterizedHeader("X-Tags", implode(", ", $tags));
    }

}
