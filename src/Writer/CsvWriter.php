<?php

namespace Asimlqt\MailchimpExport\Writer;

use Asimlqt\MailchimpExport\Writer;
use SplFileObject;

class CsvWriter implements Writer
{
    /**
     * @var SplFileObject
     */
    private $file;

    /**
     * @var boolean
     */
    private $header = false;

    public function __construct(SplFileObject $file)
    {
        $this->file = $file;
    }

    /**
     *
     * @param array $data
     *
     * @throws WriterException
     */
    public function write(array $data)
    {
        if (empty($data)) return;

        if (!$this->file->isWritable()) {
            throw new WriterException("File is not writable");
        }

        if (!$this->header) {
            $this->file->fputcsv(array_keys($data[0]));
        }

        foreach ($data as $row) {
            $this->file->fputcsv($row);
        }
    }

}
