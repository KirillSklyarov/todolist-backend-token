<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.11.2018
 * Time: 18:28
 */

namespace App\Model;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse extends JsonResponse
{
    /**
     * @var array|null
     */
    private $apiData;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var bool
     */
    private $success;

    /**
     * ApiResponse constructor.
     * @param mixed $apiData
     * @param string|null $message
     * @param bool $success
     * @param int $status
     * @param array $headers
     */
    public function __construct($apiData = null,
                                ?string $message = null,
                                bool $success = true,
                                int $status = 200,
                                array $headers = [])
    {
        parent::__construct("null", $status, $headers, true);
        $this->apiData = $apiData;
        $this->message = $message;
        $this->success = $success;
    }

    /**
     * @return array|null
     */
    public function getApiData(): ?array
    {
        return $this->apiData;
    }

    /**
     * @param array|null $apiData
     * @return ApiResponse
     */
    public function setApiData(?array $apiData): ApiResponse
    {
        $this->apiData = $apiData;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     * @return ApiResponse
     */
    public function setMessage(?string $message): ApiResponse
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return ApiResponse
     */
    public function setSuccess(bool $success): ApiResponse
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @param int $encodingOptions
     * @return JsonResponse
     */
    public function setEncodingOptions($encodingOptions)
    {
        $this->setData($this->toArray());
        return parent::setEncodingOptions($encodingOptions);
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        $this->setData($this->toArray());
        return parent::getContent();
    }

    /**
     * @return Response
     */
    public function sendContent()
    {
        $this->setData($this->toArray());
        return parent::sendContent();
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->apiData,
        ];
    }
}