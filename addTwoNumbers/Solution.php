<?php

/**
 * 两数相加
 * Class Solution
 */
class Solution
{
    /**
     * Definition for a singly-linked list.
     * class ListNode {
     *     public $val = 0;
     *     public $next = null;
     *     function __construct($val = 0, $next = null) {
     *         $this->val = $val;
     *         $this->next = $next;
     *     }
     * }
     */

    /**
     * 两数相加
     * @param $l1
     * @param $l2
     */
    function addTwoNumbers($l1, $l2)
    {
        var_dump($l1->next->val);
        var_dump($l2);
        while(isset($l1->val) && isset($l2->val)){
            $next = $l1->next;
            $l1 = $next;
            $next2 = $l2->next;
            $l2 = $next2;
        }
    }
}


class ListNode
{
    public $val = 0;
    public $next = null;

    function __construct($val = 0, $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

$l1 = [2, 4, 3];
$l2 = [5, 6, 4];
//[7,0,8]
$l11 = [9, 9, 9, 9, 9, 9, 9];
$l21 = [9, 9, 9, 9];
//[8,9,9,9,0,0,0,1];

var_dump((new Solution())->addTwoNumbers($l1, $l2));