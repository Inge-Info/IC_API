<?php
declare(strict_types=1);

require_once __DIR__ . "/../app/Loader.php";

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use app\Database;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Uri;

/**
 * Tests case for app
 */
class TestCase extends PHPUnit_TestCase
{
    /**
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @var array
     */
    protected array $test_user;

    /**
     * @param string $method
     * @param string $path
     * @param array $body
     * @param bool $connected
     * @return Request
     */
    protected function createRequest(string $method, string $path, array $body = [], bool $connected = true): Request
    {
        $uri = new Uri('', '', 80, $path);
        $stream = (new StreamFactory())->createStream(json_encode($body));

        $headers = new Headers();
        $headers->addHeader("Content-type", "application/json");
        $headers->addHeader("Cache-control", "no-cache");
        if ($connected) $headers->addHeader("Authorization", $this->test_user["username"] . ' ' . $this->test_user["token"]);

        return new Request($method, $uri, $headers, [], [], $stream);
    }

    /**
     * Generate random string
     *
     * @param int $length of the generated string
     * @return string
     */
    protected function randString(int $length = 16): string
    {
        // Password generator with custom length
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * SetUp parameters before execute tests
     */
    protected function setUp(): void {
        $this->pdo = (new Database())->getPdo();
        $this->pdo->query("DELETE FROM Users WHERE username = 'test_user_phpunit' AND is_admin = 1;")->execute();
        $token = $this->randString(60);
        $date = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s")) + $GLOBALS["config"]["session"]["token_lifetime"]);
        $test_user_id = $this->pdo->query("INSERT INTO Users (username, is_admin, token, expire) VALUES ('test_user_phpunit', 1, '$token', '$date') RETURNING id;")->fetchColumn();
        $this->test_user = $this->pdo->query("SELECT * FROM Users WHERE id = '$test_user_id' LIMIT 1;")->fetch();
        $this->response = new Response();
    }

    /**
     * Clean parameters after execute tests
     */
    protected function tearDown(): void {
        unset($this->response);
        $test_user_id = $this->test_user["id"];
        $this->pdo->query("DELETE FROM Users WHERE id = '$test_user_id';")->execute();
        unset($this->test_user);
        unset($this->pdo);
    }
}