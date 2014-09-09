<?php
/**
 * @file    auth_user.php
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\Auth;

/**
 * contains functions for handling user authentication
 * and session handling.
 */
class User extends \depage\entity\Object
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = array(
        "type" => __CLASS__,
        "id" => null,
        "name" => "",
        "fullname" => "",
        "sortname" => "",
        "passwordhash" => "",
        "email" => "",
        "settings" => "",
        "level" => 4,
        "dateRegistered" => null,
        "dateLastlogin" => null,
        "dateUpdated" => null,
        "dateResetPassword" => null,
        "confirmId" => null,
        "resetPasswordId" => null,
        "loginTimeout" => null,
    );

    /**
     * @brief primary
     **/
    static protected $primary = "id";

    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;
    // }}}

    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     *
     * @return      void
     */
    public function __construct(\depage\DB\PDO $pdo) {
        parent::__construct($pdo);

        $this->pdo = $pdo;
    }
    // }}}

    // {{{ loadByUsername()
    /**
     * gets a user-object by username directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $username   username of the user
     *
     * @return      User
     */
    static public function loadByUsername($pdo, $username) {
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                name = :name"
        );

        $uid_query->execute(array(
            ':name' => $username,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        return $user;
    }
    // }}}
    // {{{ loadByEmail()
    /**
     * gets a user-object by username directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $email      email of the user
     *
     * @return      User
     */
    static public function loadByEmail($pdo, $email) {
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                email = :email"
        );

        $uid_query->execute(array(
            ':email' => $email,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "Depage\\Auth\\User", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        return $user;
    }
    // }}}
    // {{{ loadBySid()
    /**
     * gets a user-object by sid (session-id) directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $sid        session id
     *
     * @return      auth_user
     */
    static public function loadBySid($pdo, $sid) {
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user,
                {$pdo->prefix}_auth_sessions AS sessions
            WHERE
                sessions.sid = :sid AND
                sessions.userid = user.id"
        );
        $uid_query->execute(array(
            ':sid' => $sid,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "depage\\auth\\user", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        return $user;
    }
    // }}}
    // {{{ loadById()
    /**
     * gets a user-object by id directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadById($pdo, $id) {
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user
            WHERE
                id = :id"
        );
        $uid_query->execute(array(
            ':id' => $id,
        ));

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "depage\\auth\\user", array($pdo));
        $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);

        return $user;
    }
    // }}}
    // {{{ loadAll()
    /**
     * gets an array of user-objects
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadAll($pdo) {
        $users = array();
        $fields = "type, " . implode(", ", array_keys(self::$fields));

        $uid_query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_auth_user AS user"
        );
        $uid_query->execute();

        // pass pdo-instance to constructor
        $uid_query->setFetchMode(\PDO::FETCH_CLASS, "auth_user", array($pdo));
        do {
            $user = $uid_query->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);
            if ($user) {
                array_push($users, $user);
            }
        } while ($user);

        return $users;
    }
    // }}}

    // {{{ save()
    /**
     * save a user object
     *
     * @public
     *
     * @return      auth_user
     */
    public function save() {
        $dirty = array_keys($this->dirty, true);
        $primary = self::$primary;
        $fields = array();
        $isNew = $this->data[$primary] === null;

        if (count($dirty) > 0) {
            if ($isNew) {
                $query = "INSERT INTO {$this->pdo->prefix}_auth_user";
            } else {
                $query = "UPDATE {$this->pdo->prefix}_auth_user";
            }
            foreach ($dirty as $key) {
                $fields[] = "$key=:$key";
            }
            $query .= " SET " . implode(",", $fields);

            if (!$isNew) {
                $query .= " WHERE $primary=:$primary";
                $dirty[] = $primary;
            }

            $params = array_intersect_key($this->data,  array_flip($dirty));

            $cmd = $this->pdo->prepare($query);
            $cmd->execute($params);

            if ($isNew) {
                $this->$primary = $this->pdo->lastInsertId();
            }

            $this->dirty = array_fill_keys(array_keys(static::$fields), false);
        }
    }
    // }}}

    // {{{ getUseragent()
    /**
     * gets a user-object by sid (session-id) directly from database
     *
     * @public
     *
     * @param       PDO     $pdo        pdo object for database access
     * @param       string  $sid        session id
     *
     * @return      auth_user
     */
    public function getUseragent() {
        $cachepath = DEPAGE_CACHE_PATH . "browscap/";
        if (!is_dir($cachepath)) {
            mkdir($cachepath, 0777, true);
        }

        if (ini_get("browscap")) {
            $info = get_browser($this->useragent);
        } else {
            $browscap = new browscap($cachepath);
            $browscap->silent = true;
            $browscap->doAutoUpdate = false; // don't update now
            $browscap->lowercase = true;
            //$browscap->updateMethod = Browscap::UPDATE_CURL;
            $info = $browscap->getBrowser($this->useragent);
        }

        return "{$info->browser} {$info->version} on {$info->platform}";
    }
    // }}}
    // {{{ onLogout
    /**
     * Logout
     *
     * Called when the user is logged out.
     *
     * Override in inheriting classes to provide session end functionality.
     *
     * @param $session_id
     *
     * @return void
     */
    public function onLogout($sid) {
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
