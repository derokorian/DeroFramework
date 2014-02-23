<?php

/**
 * Pagination class
 */
namespace App\Controller;

class Pager
{
    private $iPages;
    private $iCurPage;
    private $iPagesToShow;

    public function __construct($iPages, $iCurPage, $iPagesToShow = 5)
    {
        if( is_int($iPages) )
        {
            $this->iPages = $iPages;
        }
        else
        {
            throw new \UnexpectedValueException(__METHOD__ . ' expects parameter 1 to be an integer.');
        }
        if( is_int($iCurPage) )
        {
            $this->iCurPage = $iCurPage;
        }
        else
        {
            throw new \UnexpectedValueException(__METHOD__ . ' expects parameter 2 to be an integer.');
        }
        if( is_int($iPagesToShow) )
        {
            $this->iPagesToShow = $iPagesToShow;
        }
        else
        {
            throw new \UnexpectedValueException(__METHOD__ . ' expects parameter 3 to be an integer.');
        }
        $this->show();
    }

    public function show()
    {
        $aLinks = [];
        if( $this->iCurPage > 1 )
        {
            $aLinks[] = '<a href="?p=1" class="Pager">&lt;&lt;</a>';
            $aLinks[] = '<a href="?p=' . ($this->iCurPage - 1) . '" class="Pager">&lt;</a>';
        }
        else
        {
            $aLinks[] = '<span class="Pager">&lt;&lt;</span>';
            $aLinks[] = '<span class="Pager">&lt;</span>';
        }

        $iStart = $this->iCurPage - floor($this->iPagesToShow / 2);
        $iEnd = $this->iCurPage + floor($this->iPagesToShow / 2);

        if( $iStart > 1 )
        {
            $aLinks[] = '<span class="Pager">...</span>';
        }
        if( $iStart < 1 )
        {
            $iStart = 1;
        }
        if( $iEnd > $this->iPages )
        {
            $iEnd = $this->iPages;
        }
        for( $i = $iStart; $i <= $iEnd; $i++ )
        {

            $aLinks[] = '<a href="?p=' . $i . '" class="Pager">' . $i . '</a>';
        }

        if( $iEnd < $this->iPages )
        {
            $aLinks[] = '<span class="Pager">...</span>';
        }

        if( $this->iCurPage < $this->iPages )
        {
            $aLinks[] = '<a href="?p=' . ($this->iCurPage + 1) . '" class="Pager">&gt;</a>';
            $aLinks[] = '<a href="?p=' . $this->iPages . '" class="Pager">&gt;&gt;</a>';
        }
        else
        {
            $aLinks[] = '<span class="Pager">&gt;</span>';
            $aLinks[] = '<span class="Pager">&gt;&gt;</span>';
        }
        echo implode('&nbsp;', $aLinks);
    }
}