<?php
//Requirements
require_once(GCTOOLS_DIR . "database.inc.php");
require_once(ROOT_DIR . "classes/tree.inc.php");
require_once(ROOT_DIR . "classes/SpeciesTable.inc.php");
require_once(ROOT_DIR . "classes/CacheMan.inc.php");

class TreeTable { 
	private $dbres;		//Database resource
        private $cache;



        //Contructor
        public function TreeTable() {
            $this->dbres = new MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);
            $this->cache = new CacheManager();
        }

        public function ByZone($zId) {
            /*Precondition: $zId is a valid zone Id integer, Database connected and populated
             *Postcondition: Returns JSON optimized array of Tree info of trees in the zone specified
            */
            $file = ROOT_DIR . "cache/z{$zId}Tree.data";
            if (!is_file($file)) {
                $res = $this->QueryByZone($zId);
    //          echo $this->dbres->getLastError();
                $i = 0;
                while ($row = mysql_fetch_assoc($res)) {
                    $t = new Tree((int)$row['TTreeId'], (float)$row['TSpeciesId'],
                                  (float)$row['TLat'], (float)$row['TLong'],
                                  (float)$row['TDBH'], (float)$row['THeight'],
                                  (int)$row['TCrwnWidth1'], (int)$row['TCrwnWidth2']);
                    $selectedTrees[$i] = $t->getProperties();
                    $i++;
                }
                $this->cache->createDataCache($file, $selectedTrees);
            }
            $zTrees = $this->cache->readDataCache($file);
            return $zTrees;
        }

        public function getAll($admin=false) {
            $file = ROOT_DIR . "cache/allTree.data";
            if (!is_file($file)) {
                $res = $this->QueryAll();
    //          echo $this->dbres->getLastError();
                $i = 0;
                if ($admin) {$selectedTrees = 0;}//Need to write this
                else {
                    while ($row = mysql_fetch_assoc($res)) {
                        $t = new Tree((int)$row['TTreeId'], (float)$row['TSpeciesId'],
                                      (float)$row['TLat'], (float)$row['TLong'],
                                      (float)$row['TDBH'], (float)$row['THeight'],
                                      (int)$row['TCrwnWidth1'], (int)$row['TCrwnWidth2']);
                        $selectedTrees[$i] = $t->getProperties();
                        $i++;
                    }
                }
                $this->cache->createDataCache($file, $selectedTrees);
            }
            $allTrees = $this->cache->readDataCache($file);
            return $allTrees;
        }

        public function filterGenus($forest, $gid) {
            $sTable = new SpeciesTable();
            $species = $sTable->GetSpeciesByGenus($gid);
            $i = 0;
            $sid = array();
            foreach ($species as $s) {
                $sid[$i] = $s['sid'];
                $i++;
            }
            return $this->filter($forest, 'sid', $sid);
        }

        public function filterSpecies($forest, $sid) {
            return $this->filter($forest, 'sid', $sid);
        }

        private function filter($forest, $field, $value) {
            $selectedTrees = array();
            $i = 0;
            if (isset($value[0])) {
                foreach ($value as $v) {
                    foreach ($forest as $tree) {
                        if ($tree["{$field}"] == $v) {
                            $selectedTrees[$i] = $tree;
                            $i++;
                        }
                    }
                }
            }
            else {
                foreach ($forest as $tree) {
                    if ($tree["{$field}"] == $value) {
                        $selectedTrees[$i] = $tree;
                        $i++;
                    }
                }
            }
            return $selectedTrees;
        }

        public function getStats() {
            $res = array();
            array_push($res, $this->getTallest());
            $forest = $this->getAll();
            array_push($res, $this->getMCLife($forest));
            array_push($res, $this->getMCYear($forest));
            array_push($res, $this->getHeaviest($forest));
            array_push($res, $this->getOldest($forest));
            array_push($res, $this->getLDBH($forest));
            array_push($res, $this->getLCrown($forest));
            return $res;
        }

        public function JSON_getStats() {
            return json_encode($this->getStats());
        }

        public function getTallest() {
            $res = mysql_fetch_assoc($this->QueryTallest());
            $tallestId = (int)$res['TTreeId'];
            $tallestTree = new tree($tallestId);
            $prop = $tallestTree->getProperties();
            $prop['title'] = 'Tallest';
            return $prop;
        }

        public function getLCrown ($forest) { //Currently just returning filler data
            $max = array('crownarea' => 0);
            foreach ($forest as $tree) {
                if ($tree['crownarea'] > $max['crownarea']) {
                    $max = $tree;
                }
            }
            $t = new tree(1275);//These three lines to be replaced by following two
            $prop = $t->getProperties();
            $prop['title'] = 'Largest Crown Area';
            return $prop;
//          $max['title'] = 'Largest Crown Area';
//          return $max;
        }

        public function getLDBH ($forest) {
            $max = array('dbh' => 0);
            foreach ($forest as $tree) {
                if ($tree['dbh'] > $max['dbh']) {
                    $max = $tree;
                }
            }
            $max['title'] = 'Largest DBH';
            return $max;
        }

        public function getOldest ($forest) {
            $max = array('age' => 0);
            foreach ($forest as $tree) {
                if ($tree['age'] > $max['age']) {
                    $max = $tree;
                }
            }
            $max['title'] = 'Oldest';
            return $max;
        }

        public function getHeaviest ($forest) { 
            $max = array('greenwt' => 0);
            foreach ($forest as $tree) {
                if ($tree['greenwt'] > $max['greenwt']) {
                    $max = $tree;
                }
            }
            $max['title'] = 'Heaviest';
            return $max;
        }

        public function getMCLife ($forest) { 
            $max = array('co2seqwt' => 0);
            foreach ($forest as $tree) {
                if ($tree['co2seqwt'] > $max['co2seqwt']) {
                    $max = $tree;
                }
            }
            $max['title'] = 'Most CO2 Sequestered in Lifetime';
            return $max;
        }

        public function getMCyear ($forest) {
            $max = array('co2pyear' => 0);
            foreach ($forest as $tree) {
                if ($tree['co2pyear'] > $max['co2pyear']) {
                    $max = $tree;
                }
            }
            $max['title'] = 'Most CO2 Sequestered per Year';
            return $max;
        }

        private function QueryTallest() {
            return $this->dbres->query("SELECT TTreeId FROM Tree ORDER BY THeight DESC LIMIT 1");
        }
        private function QueryByZone($zone) {
            /*Precondition: $zone is a valid zone Id integer, Database connected and populated
             *Postcondition: Returns mysql_dataset of Tree info of trees in the zone specified*/
            return $this->dbres->query("SELECT TTreeId, TLat, TLong, TSpeciesId,
                          TDBH, THeight, TCrwnWidth1, TCrwnWidth2
                    FROM  Tree INNER JOIN ZoneAreaMapping ON (TAreaId = ZAPAreaId)
                    WHERE ZAPZoneId = " . $this->dbres->escapeString($zone) . " AND TRemoved = 0");
         
        }
        private function QueryAll() {
            return $this->dbres->query("SELECT TTreeId, TLat, TLong, TSpeciesId,
                          TDBH, THeight, TCrwnWidth1, TCrwnWidth2
                    FROM  Tree
                    WHERE TRemoved = 0");
         
        }
}
?>
