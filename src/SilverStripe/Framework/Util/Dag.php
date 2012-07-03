<?php
/**
 * @package framework
 * @subpackage util
 */

namespace SilverStripe\Framework\Util;

/**
 * A Directed Acyclic Graph - used for doing topological sorts on dependencies,
 * such as the before/after conditions in config yaml fragments.
 *
 * @package framework
 * @subpackage util
 */
class Dag {

	/**
	 * @var array The nodes in the graph.
	 */
	protected $data = array();

	/**
	 * @var array The edges in the graph, with the to side as the keys and the
	 *      from side as the values.
	 */
	protected $dag = array();

	/**
	 * @param array $data
	 */
	public function __construct(array $data = array()) {
		if($data) {
			$this->data = array_values($data);
			$this->dag  = array_fill_keys(array_keys($this->data), array());
		}
	}

	/**
	 * @param $item anything - The item to add to the graph
	 */
	public function addItem($item) {
		$this->data[] = $item;
		$this->dag[] = array();
	}

	/**
	 * Add an edge from one vertex to another. The parameters can either be the
	 * numeric index of the node, or the actual node value.
	 *
	 * When passing actual nodes (as opposed to indexes) a strict array search
	 * is used to find the node.
	 *
	 * @param int|mixed $from
	 * @param int|mixed $to
	 */
	public function addEdge($from, $to) {
		$i = is_numeric($from) ? $from : array_search($from, $this->data, true);
		$j = is_numeric($to) ? $to : array_search($to, $this->data, true);

		if ($i === false) throw new \Exception("Couldnt find 'from' item in data when adding edge to DAG");
		if ($j === false) throw new \Exception("Couldnt find 'to' item in data when adding edge to DAG");

		if (!isset($this->dag[$j])) $this->dag[$j] = array();
		$this->dag[$j][] = $i;
	}

	/**
	 * Sorts graph so that each node (a) comes before any nodes (b) where an
	 * edge exists from a to b.
	 *
	 * @return array
	 * @throws Exception If the graph is cyclic and cannot be sorted.
	 */
	public function sort() {
		$data = $this->data; $dag = $this->dag; $sorted = array();

		while (true) {
			$withedges = array_filter($dag, 'count');
			$starts = array_diff_key($dag, $withedges);

			if (!count($starts)) break;

			foreach ($starts as $i => $foo) $sorted[] = $data[$i];

			foreach ($withedges as $j => $deps) {
				$withedges[$j] = array_diff($withedges[$j], array_keys($starts));
			}

			$dag = $withedges;
		}

		if ($dag) throw new \Exception("DAG has cyclic requirements");
		return $sorted;
	}

}
