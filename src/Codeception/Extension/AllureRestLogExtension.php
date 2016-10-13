<?php
namespace Codeception\Extension;

use Yandex\Allure\Adapter\Support\AttachmentSupport;
use Codeception\Extension;
use Codeception\Event\FailEvent;
use Codeception\Event\StepEvent;
use Codeception\Event\TestEvent;
use \Symfony\Component\BrowserKit\Request;
use \Symfony\Component\BrowserKit\Response;
use \Symfony\Component\BrowserKit\Client;

/**
 * Class AllureRestLogExtension
 *
 * Add REST request log to Allure Framework for failed tests
 */
class AllureRestLogExtension extends Extension
{
    use AttachmentSupport;

    public $log = [];

    // list events to listen to
    public static $events = [
        'test.start' => 'testStarted',
        'test.fail' => 'testFailed',
        'test.incomplete' => 'testIncompleted',
        'test.skipped' => 'testSkipped',
        'test.error' => 'testError',
        'step.after' => 'stepAfter'
    ];

    /**
     * Get test info and create first log entry
     *
     * @param TestEvent $e
     */
    public function testStarted($e)
    {
        $test = $e->getTest();
        $env = $test->getMetadata()->getCurrent('env');
        if ($env) {
            $envCommand = '--env ' . $env . ' ';
        } else {
            $envCommand = '';
        }
        $fileName = $test->getMetadata()->getFilename();
        $path = substr($fileName, strpos($fileName, 'tests'));
        $this->log = [
            'codecept run ' . $envCommand . '-d ' . $path . ':<b>' . $test->getName() . '</b>'
        ];
    }

    /**
     * Attach log in case of failed test
     *
     * @param FailEvent $e
     */
    public function testFailed($e)
    {
        $this->attachLog($e);
    }

    /**
     * Attach log in case of incomplete test
     *
     * @param FailEvent $e
     */
    public function testIncompleted($e)
    {
        $this->attachLog($e);
    }

    /**
     * Attach log in case of skipped test
     *
     * @param FailEvent $e
     */
    public function testSkipped($e)
    {
        $this->attachLog($e);
    }

    /**
     * Attach log in case of test with error
     *
     * @param FailEvent $e
     */
    public function testError($e)
    {
        $this->attachLog($e);
    }

    /**
     * Get last request info and add it to log
     *
     * @param StepEvent $e
     */
    public function stepAfter($e)
    {
        $requestData = $this->getRequestInfo();
        if ($requestData) {
            $this->addToLog($this->formatRequestData($requestData));
        }
    }

    /**
     * Attach log file to Allure Framework
     *
     * @param FailEvent $e
     */
    protected function attachLog($e)
    {
        $data = '';
        $log = array_unique($this->log);
        if (count($log) > 1) { // skip empty files - with only testname entry
            $this->addToLog('Fail:' . $e->getFail()->getMessage());
            foreach ($log as $entry) {
                $data .= $entry . '<hr style="margin: 20px 0">';
            }
            $logName = 'requestLog' . time() . $e->getTest()->getName();
            $logFile = codecept_output_dir($logName);
            file_put_contents($logFile, $data);
            $this->addAttachment($logFile, 'Request Log', 'text/html');
        }
    }

    /**
     * Get info about last request
     *
     * @return array
     */
    protected function getRequestInfo()
    {
        $rest = $this->getModule('REST');
        try
        {
            /** @var Client $client */
            $client = $rest->client;

            /** @var Response $response */
            $response = $client->getInternalResponse();
            if ($response) {
                $code = $response->getStatus();
                $responseHeaders = $response->getHeaders();
            }

            /** @var Request $request */
            $request = $client->getInternalRequest();

            if ($request) {
                $url = ($request->getUri());
            };

            $data = [
                'date' => $responseHeaders['Date'][0],
                'url' => $url,
                'params' => $rest->params,
                'code' => $code,
                'response' => $rest->response,
            ];

            return $data;
        }
        catch (Exception $e)
        {
        }
    }

    /**
     * Pretty format request info
     *
     * @param array $data Request info
     * @return string
     */
    protected function formatRequestData($data)
    {
        if ($this->isJson($data['response'])) {
            $data['response'] = json_encode(json_decode($data['response']), JSON_PRETTY_PRINT);
        } elseif ($this->isXml($data['response'])) {
            $data['response'] = htmlspecialchars($data['response'], ENT_XML1, 'UTF-8');
        }
        $paramsPretty = $this->printArray($data['params']);
        $result =
            '<style type="text/css">div{margin: 10px 0;}</style>' .
            '<div style="color: grey">' . date('Y-m-d H:i:s', strtotime($data['date'])) . '</div>' .
            '<div><b>' . $data['url'] . '</b></div>' .
            '<pre>' . $paramsPretty . '</pre>' .
            '<div><b>Response: ' . \Codeception\Util\HttpCode::getDescription($data['code']) . '</b>';
        if (mb_strlen($data['response'], 'utf-8') > 500) {
            $result .= '<details><pre>' . $data['response'] . '</pre></details>';
        } else {
            $result .= '<pre>' . $data['response'] . '</pre></div>';
        }
        return $result;
    }

    /**
     * Pretty print array
     *
     * @param array $array
     * @param string $sourceKey
     * @return string
     */
    protected function printArray(&$array, $sourceKey = null)
    {
        $str = '';
        foreach ($array as $key => $value) {
            if ($sourceKey !== null) {
                $key = $sourceKey . '[' . $key . ']';
            }

            if (is_array($value)) {
                $str .= $this->printArray($value, $key);
            } else {
                if ($value === null) {
                    $value = 'null';
                } elseif ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                }
                $str .= $key . ': ' . $value . PHP_EOL;
            }
        }
        return rtrim($str);
    }

    /**
     * Check string is json or not
     *
     * @param string $string
     * @return bool
     */
    protected function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Check string is xml or not
     *
     * @param string $string
     * @return bool
     */
    protected function isXml($string)
    {
        libxml_use_internal_errors(true);
        return simplexml_load_string($string);
    }

    /**
     * Add message to log
     *
     * @param string $data Log message
     */
    protected function addToLog($data)
    {
        $this->log[] = $data;
    }
}