<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

/**
 * Sends your current Now Playing track to a database.
 * 
 * For command line setup @see CLIDBPlugin
 */
class DBPlugin implements SSLPlugin, NowPlayingObserver
{
    protected $config;
    protected $key;
    
    /**
     * Map of placeholders actually used in the SQL statement
     * 
     * @var array
     */
    protected $placeholder_map = array(
        ':album' => false,
        ':artist' => false,
        ':track' => false,
        ':title' => false,
        ':key' => false
    );
    
    /**
     * @var PDO
     */
    protected $dbh;
    
    /**
     * @var PDOStatement
     */
    protected $sth;

    public function __construct(array $config, $key)
    {
        $this->setConfig($config);
        $this->key = $key;
    }
    
    public function onSetup() 
    {
    }
    
    public function onStart()
    {
        $config = $this->config;
        $this->dbh = new PDO($config['dsn'], $config['user'], $config['pass'], $config['options']);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->exec('SET CHARACTER SET utf8');
        $this->setPlaceholderMapFromSQL($config['sql']);
        $this->sth = $this->dbh->prepare($config['sql']);
    }
    
    public function onStop()
    {
    }
    
    public function getObservers()
    {
        return array(
            $this
        );
    }

    public function setConfig(array $config)
    { 
        $this->config = $config;
    }

    public function notifyNowPlaying(SSLTrack $track=null)
    {
        if($track) 
        {
            $placeholders = $this->getPlaceholdersFromTrack($track, $this->placeholder_map);
        }
        else
        {
            $placeholders = $this->getPlaceholdersForNoTrack($this->placeholder_map);
        }
        $this->sth->execute( $placeholders );
    }
    
    protected function getPlaceholdersFromTrack(SSLTrack $track, array $placeholder_map)
    {
        $placeholders = array();
        if($placeholder_map[':track'])  $placeholders[':track']  = $track->getFullTitle();
        if($placeholder_map[':artist']) $placeholders[':artist'] = $track->getArtist();
        if($placeholder_map[':title'])  $placeholders[':title']  = $track->getTitle();
        if($placeholder_map[':album'])  $placeholders[':album']  = $track->getAlbum();
        if($placeholder_map[':key'])    $placeholders[':key']    = $this->key;
        return $placeholders;
    }
    
    protected function getPlaceholdersForNoTrack(array $placeholder_map)
    {
        $placeholders = array();
        if($placeholder_map[':track'])  $placeholders[':track']  = $this->config['empty_string'];
        if($placeholder_map[':artist']) $placeholders[':artist'] = '';
        if($placeholder_map[':title'])  $placeholders[':title']  = '';
        if($placeholder_map[':album'])  $placeholders[':album']  = '';
        if($placeholder_map[':key'])    $placeholders[':key']    = $this->key;
        return $placeholders;
    }
    
    protected function setPlaceholdersMapFromSQL($sql)
    {
        if(preg_match_all('/:[a-z]+/', $sql, $matches)) 
        {
            foreach($matches[0] as $placeholder)
            {
                $this->placeholder_map[$placeholder] = true;
            }
        }
    }
}
