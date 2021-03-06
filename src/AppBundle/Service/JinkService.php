<?php
declare(strict_types=1);

namespace AppBundle\Service;
use AppBundle\Command\Trader\Trade\Trade;
use AppBundle\Entity\Event;
use AppBundle\Entity\Log;

/**
 * Class JinkService
 * @package AppBundle\Service
 */
class JinkService
{
    private const TOKEN_PARAM_NAME = 'X-AUTH-TOKEN';
    private const HTTP_OK = 200;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $apiUrl;

    /** @var string */
    private $clientId;

    /** @var integer */
    private $lastSignalId;

    /** @var bool */
    private $productionMode;


    /**
     * JinkService constructor.
     * @param $jinkApiKey
     * @param $jinkApiUrl
     * @param $clientId
     * @param $production
     * @param string|null $jinkClientId
     */
    public function __construct($jinkApiKey, $jinkApiUrl, $clientId, $production, string $jinkClientId = null)
    {
        $this->apiKey = $jinkApiKey;
        $this->apiUrl = $jinkApiUrl;
        $this->clientId = $clientId;
        $this->setProductionMode($production);

        if (!$jinkClientId) {
            $this->registerClient();
            $log = new Log("Registered new client ID: " . $this->getClientId(), Log::LOG_LEVEL_INFO);
            $this->postLog($log);
        }
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     */
    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return int
     */
    public function getLastSignalId(): int
    {
        return $this->lastSignalId;
    }

    /**
     * @param int $lastSignalId
     */
    public function setLastSignalId(int $lastSignalId): void
    {
        $this->lastSignalId = $lastSignalId;
    }

    /**
     * @return bool
     */
    public function isProduction()
    {
        return $this->productionMode;
    }

    /**
     * @return bool
     */
    public function isProductionMode(): bool
    {
        return $this->productionMode;
    }

    /**
     * @param bool $productionMode
     */
    public function setProductionMode(bool $productionMode): void
    {
        $this->productionMode = $productionMode;
    }


    /**
     * @return bool
     */
    public function registerClient(): bool
    {
        $body = json_encode([
            'client_id' => $this->getClientId()
        ]);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'client');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'accept: application/json',
                    'charset=utf-8',
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );

            $result = curl_exec($ch);
            unset($body);

            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                curl_close($ch);
                unset($ch);
                $result = json_decode($result, true);
                $this->setLastSignalId($result['lastSignalId']);
                return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        unset($ch);
        return false;
    }


    /**
     * @param array $events
     * @return bool
     */
    public function postEvents(array $events): bool
    {
        $body = [];
        /** @var Event $e */
        foreach ($events as $e) {
            $event = [];
            $event['action'] = $e->getAction();
            $event['basic_token'] = $e->getTrade()->getBasicToken();
            $event['amount'] = $e->getTrade()->getAmount();
            $event['token'] = $e->getTrade()->getToken();
            $event['price'] = ($e->getAction()==Event::ACTION_BUY?$e->getTrade()->getPrice()->getBuy():$e->getTrade()->getPrice()->getCurrent());
            $event['profit'] = $e->getTrade()->getCurrent()->getProfit();
            $event['signal'] = $e->getTrade()->getSignal();
            $event['timestamp'] = $e->getCreatedAt()->format("Y-m-d H:i:s");
            $body[] = $event;
        }
        unset($events);
        $body = json_encode($body);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'events?client_id='.$this->getClientId());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'accept: application/json',
                    'charset=utf-8',
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );

            $result = curl_exec($ch);

            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }

    /**
     * @param Trade $trade
     * @return bool|mixed
     */
    public function getEventState(Trade $trade)
    {
        $signal = $trade->getSignal();
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'event/state?client_id='.$this->getClientId().'&signal_id='.$signal['signal']['id'].'&token='.$trade->getToken().'&basic_token='.$trade->getBasicToken());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );
            $result = curl_exec($ch);

            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                curl_close($ch);
                unset($ch);
                $result = json_decode($result, true);
                if (isset($result['state'])) {
                    return $result['state'];
                }
                return false;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        unset($ch);
        return false;
    }

    /**
     * @param array $logs
     * @return bool
     */
    public function postLogs(array $logs)
    {
        $body = [];
        foreach ($logs as $l) {
            $log = [];
            $log['text'] = (!$this->isProduction()?'[dev] ':'').$l->getText();
            $log['level'] = $l->getLevel();
            $log['timestamp'] = $l->getCreatedAt()->format("Y-m-d H:i:s");
            $body[] = $log;
        }
        unset($logs);
        $body = json_encode($body);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'logs?client_id='.$this->getClientId());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'accept: application/json',
                    'charset=utf-8',
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );

            $result = curl_exec($ch);
            unset($body);

            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                curl_close($ch);
                unset($ch);
                return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        unset($ch);
        return false;
    }

    /**
     * @param Log $log
     * @return bool
     */
    public function postLog(Log $log): bool
    {
        $body = json_encode([
            'text' => (!$this->isProduction()?'[dev] ':'').$log->getText(),
            'level' => $log->getLevel(),
            'timestamp' => $log->getCreatedAt()->format("Y-m-d H:i:s")
        ]);
        unset($log);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'log?client_id='.$this->getClientId());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'accept: application/json',
                    'charset=utf-8',
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );

            $result = curl_exec($ch);
            unset($body);

            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                curl_close($ch);
                unset($ch);
                return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        unset($ch);
        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getSignal()
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'signal?client_id='.$this->getClientId().'&last_signal_id='.$this->getLastSignalId());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );
            $result = curl_exec($ch);

            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                curl_close($ch);
                unset($ch);
                $result = json_decode($result, true);
                if (isset($result['signal']['id'])) {
                    $this->setLastSignalId($result['signal']['id']);
                }
                return $result;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        unset($ch);
        return false;
    }

    /**
     * @return bool
     */
    public function updateLastSignal(): bool
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl .'signal/last?client_id='.$this->getClientId());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    $this::TOKEN_PARAM_NAME.': '.$this->getApiKey(),
                    'production: '.($this->isProduction()?'1':'0')
                ]
            );
            $result = curl_exec($ch);
            if ($result === false) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === $this::HTTP_OK) {
                curl_close($ch);
                unset($ch);
                $result = json_decode($result, true);
                $this->setLastSignalId($result['signal']['id']);
                return true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        unset($ch);
        return false;
    }
}