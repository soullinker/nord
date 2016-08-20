<?php

class Node
{
	private $name = "";

	public $parent = null;
	public $child = [];

	function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		$name = $this->name; // should use "readonly" since php7
		return $name;
	}

	public function toArray()
	{
		$array = ['name' => $this->name, 'child' => []];
		foreach ($this->child as $child)
			$array['child'][] = $child->toArray();

		return $array;
	}
}

// пришлось убрать "protected"

abstract class Tree
{
	// создает узел (если $parentNode == NULL - корень)
	abstract function createNode(Node $node, $parentNode = NULL);

	// удаляет узел и все дочерние узлы
	abstract function deleteNode(Node $node);

	// один узел делает дочерним по отношению к другому
	abstract function attachNode(Node $node, Node $parent);

	// получает узел по названию
	abstract function getNode($nodeName);

	// преобразует дерево со всеми элементами в ассоциативный массив
	abstract function export();
}

// не понятно поведение при создании root, когда таковой уже есть
// не понятно поведение при создании нод с одинаковыми именами
// возможен кейс с замыканием дерева!
// Tree должен иметь один root элемент с parent==null, а это кустарник* :)
// *разъедененный плоский компланарный граф (кажется так*)

class MyTree extends Tree
{
	protected $root = [];

	public function getNode($name)
	{
		// TODO: оптимизировать поиск чтобы не перебирать все поодряд
		foreach ($this->root as $node)
		{
			if ($search = $this->searchNode($node, $name))
				return $search;
		}

		return null;
	}

	protected function searchNode(Node $node, $name)
	{
		if ($node->getName() == $name)
			return $node;
		foreach ($node->child as $child)
		{
			if ($search = $this->searchNode($child, $name))
				return $search;
		}

		return null;
	}

	// создает узел (если $parentNode == NULL - корень)
	// == добавляет ? (создает его оператор new)
	public function createNode(Node $node, $parent = NULL)
	{
		if ($parent === null) {
			$this->root[] = $node;
			$node->parent = null;

			//изза необязательного parent придется делать parent=null

			// вообще createNode должна быть функция класса Node а не Tree
			// а Tree указатель на рутовую ноду (или на null)
			// Node->add(Node child) {
			//     $this->child[] = $child;
			//     $child->parent = $this;
			// }
			// тем самым исключая возможность запутывания дерева
			// пример:
			// $r = new Node('root');
			// $u = new Node('underground');
			// $tree->attachNode($u,$r);
			// $tree->attachNode($r,$r);
			// $tree->createNode($r);
		} else {
			$this->attachNode($node, $parent);
		}
	}

	private function removeRootNode(Node $node)
	{
		$name = $node->getName();
		foreach ($this->root as $index => $node)
		{
			if ($name == $node->getName())
			{
				array_splice($this->root, $index, 1);
				break;
			}
		}
	}

	// удаляет узел и все дочерние узлы
	public function deleteNode(Node $node)
	{
		$node = $this->getMyNode($node);
		$this->removeFromParent($node);
	}

	protected function isNested(Node $what, Node $where)
	{
		while ($where)
		{
			// Two object instances are equal if they have the same attributes and values,
			// and are instances of the same class
			if ($where == $what)
				return true;
			$where = $where->parent;
		}

		return false;
	}

	// один узел делает дочерним по отношению к другому
	public function attachNode(Node $node, Node $parent)
	{
		$exist_node = $this->getMyNode($node);
		$parent = $this->getMyNode($parent);

		if (!$parent)
			die("Cannot find node to attach to");

		if ($exist_node)
		{
			$node = $exist_node;
			$this->removeFromParent($node);
		}

		if ($this->isNested($parent, $node))
			die("Cannot move node in his child or itself\n");

		$node->parent = $parent;
		$parent->child[] = $node;
	}

	protected function removeFromParent(Node $node)
	{
		$parent = $node->parent;

		if ($parent)
		{
			$name = $node->getName();
			foreach ($parent->child as $index => $child)
			{
				if ($child->getName() == $name)
				{
					array_splice($parent->child, $index, 1);
					break; // ?
				}
			}
			$node->parent = null;
		} else {
			// its a root node
			$this->removeRootNode($node);
		}
	}

	// поиск через сравнение по строке не самый надежный метод
	// а еще я верю в "dont ask - tell"
	private function getMyNode(Node $node)
	{
		$mynode = $this->getNode($node->getName());
		if (!$mynode)
			echo ("Cannot find node by object\n");

		return $mynode;
	}

	// преобразует дерево со всеми элементами в ассоциативный массив
	public function export()
	{
		$array = [];
		foreach ($this->root as $node)
			$array[] = $node->toArray();

		return $array;
	}
}

$tree = new MyTree();

//Обеспечить выполнение следующего теста:
// 1. создать корень country
$tree->createNode(new Node('country'));
// 2. создать в нем узел kiev
$tree->createNode(new Node('kiev'), $tree->getNode('country'));
// 3. в узле kiev создать узел kremlin
$tree->createNode(new Node('kremlin'), $tree->getNode('kiev'));
// 4. в узле kremlin создать узел house
$tree->createNode(new Node('house'), $tree->getNode('kremlin'));
// 5. в узле kremlin создать узел tower
$tree->createNode(new Node('tower'), $tree->getNode('kremlin'));
// 4. в корневом узле создать узел moskow
$tree->createNode(new Node('moskow'), $tree->getNode('country'));
// 5. сделать узел kremlin дочерним узлом у moskow
$tree->attachNode($tree->getNode('kremlin'), $tree->getNode('moskow'));
// 6. в узле kiev создать узел maidan
$tree->createNode(new Node('maidan'), $tree->getNode('kiev'));
// 7. удалить узел kiev
$tree->deleteNode($tree->getNode('kiev'));
// 8. получить дерево в виде массива, сделать print_r
print_r($tree->export());

// и разделить ноды и данные...
// ну и, если типизировать параметры, то и возвращаемое значение тоже.