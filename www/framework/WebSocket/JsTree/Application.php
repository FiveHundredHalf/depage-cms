<?php

require_once("framework/Depage/Runner.php");

class JsTreeApplication implements \Wrench\Application\DataHandlerInterface,
    Wrench\Application\ConnectionHandlerInterface,
    Wrench\Application\UpdateHandlerInterface
{
    private $clients = [];
    private $deltaUpdates = [];
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        'env' => "development",
        'timezone' => "UST",
    );

    function __construct() {
        $conf = new \Depage\Config\Config();
        $conf->readConfig(__DIR__ . "/../../../conf/dpconf.php");
        $this->options = $conf->getFromDefaults($this->defaults);

        // get database instance
        $this->pdo = new \Depage\Db\Pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        /* get auth object
        $this->auth = \Depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        ); */
    }

    public function onConnect(Wrench\Connection $client): void
    {
    }

    public function onDisconnect(Wrench\Connection $client): void
    {
        foreach ($this->clients as $cid => $clients) {
            $this->unsubscribe($client, $cid);
        }
    }

    public function onUpdate() {
        foreach ($this->clients as $cid => $clients) {
            $data = $this->deltaUpdates[$cid]->encodedDeltaUpdate();

            if (!empty($data)) {
                // send to clients
                foreach ($clients as $client) {
                    try {
                        $client->send($data);
                    } catch (\Wrench\Exception\SocketException $e) {
                        $this->onDisconnect($client);
                    }
                }
            }
        }

        // do not sleep too long, this impacts new incoming connections
        usleep(50 * 1000);
    }

    public function onData(string $data, Wrench\Connection $client):void
    {
        $data = json_decode($data);
        if (!$data) return;

        if ($data->action == "subscribe") {
            $this->subscribe($client, $data->projectName, $data->docId);
        } else if ($data->action == "unsubscribe") {
            $this->unsubscribe($client, "{$data->projectName}_{$data->docId}");
        }
    }

    // {{{ subscribe()
    /**
     * @brief subscribe
     *
     * @param mixed $client
     * @return void
     **/
    protected function subscribe($client, $projectName, $docId)
    {
        $cid = "{$projectName}_{$docId}";

        if (empty($this->clients[$cid])) {
            $this->clients[$cid] = [];
            $prefix = "{$this->pdo->prefix}_proj_{$projectName}";
            $xmldbCache = \Depage\Cache\Cache::factory($prefix, array(
                'disposition' => "redis",
                'host' => "127.0.0.1:6379",
            ));
            $project = \Depage\Cms\Project::loadByName($this->pdo, $xmldbCache, $projectName);
            $xmldb = $project->getXmlDb();

            $this->deltaUpdates[$cid] = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->pdo, $xmldb, $docId, $projectName);
        }

        $this->clients[$cid][$client->getId()] = $client;
    }
    // }}}
    // {{{ unsubscribe()
    /**
     * @brief unsubscribe
     *
     * @param mixed $
     * @return void
     **/
    protected function unsubscribe($client, $cid)
    {
        $id = $client->getId();
        if (isset($this->clients[$cid][$id])) {
            unset($this->clients[$cid][$id]);

            if (empty($this->clients[$cid])) {
                unset($this->clients[$cid]);
                unset($this->deltaUpdates[$cid]);
            }
        }
    }
    // }}}
}

?>
