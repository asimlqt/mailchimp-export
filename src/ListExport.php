<?php

namespace Asimlqt\MailchimpExport;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class ListExport
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $listId;

    /**
     * @var string
     */
    private $writer;

    public function __construct(string $apiKey, string $listId, Writer $writer)
    {
        $this->apiKey = $apiKey;
        $this->listId = $listId;
        $this->writer = $writer;
    }

    /**
     * Run the exporter
     *
     * @throws MailchimpException
     */
    public function run()
    {
        $response = $this->makeRequest();

        $body = $response->getBody();
        $header = [];

        while (!$body->eof()) {
            $contents = trim($body->getContents());

            if (empty($contents)) continue;

            $data = explode("\n", $contents);

            if (empty($header)) {
                $header = json_decode(array_shift($data), true);

                if (isset($header["error"])) {
                    throw new MailchimpException($header["error"], $header["code"]);
                }
            }

            $rows = [];
            foreach ($data as $row) {
                $rows[] = array_combine($header, json_decode($row, true));
            }

            $this->writer->write($rows);
        }
    }

    private function makeRequest(): Response
    {
        $client = new Client(["stream" => true]);
        return $client->request(
            "POST",
            $this->getUrl(),
            [
                "json" => [
                    "apikey" => $this->apiKey,
                    "id" => $this->listId
                ]
            ]
        );
    }

    private function getUrl(): string
    {
        return sprintf(
            "https://%s.api.mailchimp.com/export/1.0/list/",
            explode("-", $this->apiKey)[1]
        );
    }

}
