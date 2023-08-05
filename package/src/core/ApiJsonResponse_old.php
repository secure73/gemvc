<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Core;

class ApiJsonResponse_old
{
    public int $http_response;
    public string $http_message;
    public int $count;
    public ?string $service_message;

    /**
     * @var array<mixed>
     */
    public array $data;

    public function __construct()
    {
        $this->data = [];
    }
    /**
     * @param array<mixed>|null $data
     */
    public static function success(int $count, mixed $data = null, string $message = null): self
    {
        $ins = new self();
        $ins->http_message = 'success';
        $ins->count = $count;
        $ins->http_response = 200;
        $ins->setData($data);
        if ($message) {
            $ins->service_message = $message;
        }

        return $ins;
    }

    public static function error(int $errorCode, string $message): self
    {
        $ins = new self();
        $ins->http_message = $message;
        $ins->http_response = $errorCode;
        $ins->count = 0;
        $ins->service_message = $message;

        return $ins;
    }
    /**
     * @param array<mixed>|null $data
     */
    public static function created(int $count, mixed $data, string $message = null): self
    {
        $ins = new self();
        $ins->http_message = 'Created';
        $ins->count = $count;
        $ins->http_response = 201;
        $ins->setData($data);
        if ($message) {
            $ins->service_message = $message;
        }

        return $ins;
    }
    /**
     * @param array<mixed>|null $data
     */
    public static function updated(int $count, mixed $data, string $message = null): self
    {
        $ins = new self();
        $ins->http_message = 'Updated';
        $ins->count = $count;
        $ins->http_response = 209;
        $ins->setData($data);
        if ($message) {
            $ins->service_message = $message;
        }

        return $ins;
    }

    /**
     * @param array<mixed>|null $data
     */
    public static function deleted(int $count, mixed $data, string $message = null): self
    {
        $ins = new self();
        $ins->http_message = 'Deleted';
        $ins->count = $count;
        $ins->http_response = 210;
        $ins->setData($data);
        if ($message) {
            $ins->service_message = $message;
        }

        return $ins;
    }

    public static function unauthorized(string $error_message = null): self
    {
        $ins = new self();
        $ins->http_message = 'Unauthorized';
        $ins->http_response = 401;
        $ins->count = 0;
        $ins->service_message = $error_message;

        return $ins;
    }

    public static function forbidden(string $error_message = null): self
    {
        $ins = new self();
        $ins->http_message = 'Access denied';
        $ins->http_response = 403;
        $ins->count = 0;
        $ins->service_message = $error_message;

        return $ins;
    }

    public static function notFound(string $request_url = null): self
    {
        $ins = new self();
        $ins->http_message = 'not found';
        $ins->http_response = 404;
        $ins->service_message = "Request {$request_url} not found";

        return $ins;
    }

    public static function internalServer(string $error_message = null): self
    {
        $ins = new self();
        $ins->http_response = 500;
        $ins->http_message = 'Internal Server Error';
        $ins->service_message = $error_message;
        $ins->data = [];

        return $ins;
    }

    public static function badRequest(string $error_message = null): self
    {
        $ins = new self();
        $ins->http_message = 'Bad Request';
        $ins->http_response = 400;
        $ins->service_message = $error_message;

        return $ins;
    }

    /**
     * @param array<mixed> $suggested_errors
     */
    public static function helper(array $suggested_errors): self
    {
        $ins = new self();
        $ins->http_message = 'Bad Request';
        $ins->http_response = 400;
        $ins->service_message = 'please add ?help at the end of your requesrt url to get more information';
        $ins->data = $suggested_errors;

        return $ins;
    }

    public function show(): void
    {
        http_response_code($this->http_response);
        echo json_encode($this, JSON_PRETTY_PRINT);
    }

    /**
     * @param null|array<mixed> $data
     */
    public static function onflySuccess(int $count, array $data = null, string $message = null): void
    {
        $msg = self::success($count, $data, $message);
        $msg->show();
    }
    /**
     * @param array<mixed>|null $data
     */
    public static function onflyCreated(int $count, mixed $data, string $message = null): void
    {
        $msg = self::created($count, $data, $message);
        $msg->show();
    }
    /**
     * @param array<mixed>|null $data
     */
    public static function onflyUpdated(int $count, mixed $data, string $message = null): void
    {
        $msg = self::updated($count, $data, $message);
        $msg->show();
    }
    /**
     * @param array<mixed>|null $data
     */
    public static function onflyDeleted(int $count, mixed $data, string $message = null): void
    {
        $msg = self::deleted($count, $data, $message);
        $msg->show();
    }

    public static function onflyUnauthorized(string $error_message = null): void
    {
        $msg = self::unauthorized($error_message);
        $msg->show();
    }

    public static function onflyForbidden(string $error_message = null): void
    {
        $msg = self::forbidden($error_message);
        $msg->show();
    }

    public static function onflyNotFound(string $request_url = null): void
    {
        $msg = self::notFound($request_url);
        $msg->show();
    }

    public static function onflyInternalServer(string $error_message = null): void
    {
        $msg = self::internalServer($error_message);
        $msg->show();
    }

    public static function onflyBadRequest(string $error_message = null): void
    {
        $msg = self::badRequest($error_message);
        $msg->show();
    }

    /**
     * @param array<mixed> $suggested_errors
     */
    public static function onflyHelper(array $suggested_errors): void
    {
        $ins = new self();
        $ins->http_message = 'Bad Request';
        $ins->http_response = 400;
        $ins->service_message = 'please add ?help at the end of your requesrt url to get more information';
        $ins->data = $suggested_errors;
        $ins->show();
    }

    /**
     * @param array<mixed>|null $data
     */
    private function setData(mixed $data): void
    {
        if ($data) {
            $this->data = $data;
        }
    }
}
