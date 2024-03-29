<?php

namespace SomeBlackMagic\GuzzleProfilerBundle\Log;

class LogMessage
{
    /** @var string */
    protected $message;

    /** @var string */
    protected $level;

    /** @var LogRequest */
    protected $request;

    /** @var LogResponse */
    protected $response;

    /** @var null|float */
    protected $transferTime;

    /** @var null|string */
    protected $curlCommand;

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Set log level
     *
     * @param string $level
     *
     * @return void
     */
    public function setLevel($level) : void
    {
        $this->level = $level;
    }

    /**
     * Returning log level
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Returning log message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set Log Request
     *
     * @param LogRequest $value
     *
     * @return void
     */
    public function setRequest(LogRequest $value) : void
    {
        $this->request = $value;
    }

    /**
     * Get Log Request
     *
     * @return LogRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set Log Response
     *
     * @param LogResponse $value
     *
     * @return void
     */
    public function setResponse(LogResponse $value)
    {
        $this->response = $value;
    }

    /**
     * Get Log Response
     *
     * @return LogResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return float|null
     */
    public function getTransferTime()
    {
        return $this->transferTime;
    }

    /**
     * @param float|null $transferTime
     *
     * @return void
     */
    public function setTransferTime($transferTime) : void
    {
        $this->transferTime = $transferTime;
    }

    /**
     * @return null|string
     */
    public function getCurlCommand()
    {
        return $this->curlCommand;
    }

    /**
     * @param string $curlCommand
     *
     * @return void
     */
    public function setCurlCommand($curlCommand) : void
    {
        $this->curlCommand = $curlCommand;
    }
}
