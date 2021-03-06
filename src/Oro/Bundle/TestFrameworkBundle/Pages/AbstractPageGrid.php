<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

use PHPUnit_Framework_Assert;

/**
 * Class AbstractPageGrid
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 * {@inheritdoc}
 */
abstract class AbstractPageGrid extends AbstractPage
{

    protected $gridPath = '';

    protected $filtersPath = '';


    /**
     * @param array $entityData
     *
     * @return mixed
     */
    abstract public function open($entityData = array());

    /**
     * Select random entity from current page
     *
     * @param int $pageSize
     * @return array
     */
    public function getRandomEntity($pageSize = 10)
    {
        $pageSize = min($pageSize, $this->getRowsCount());
        $entityId = rand(1, $pageSize);

        $entity = $this->test
            ->elements(
                $this->test->using('xpath')
                    ->value("{$this->gridPath}//table[contains(@class,'grid')]/tbody/tr[{$entityId}]/td")
            );
        $headers = $this->test
            ->elements(
                $this->test->using('xpath')
                    ->value("{$this->gridPath}//table[contains(@class,'grid')]/thead/tr/th")
            );

        $entityData = array();
        for ($i=0; $i< count($headers); $i++) {
            $entityData[$headers[$i]->text()] = $entity[$i]->text();
        }
        return $entityData;
    }

    /**
     * Change current grid page
     *
     * @param int $page
     * @return $this
     */
    public function changePage($page = 1)
    {
        $pager = $this->test->byXPath("{$this->filtersPath}//div[contains(@class,'pagination')]/ul//input");
        $pagerLabel = $this->test->byXPath(
            "{$this->filtersPath}//div[contains(@class,'pagination')]/label[@class = 'dib' and text() = 'Page:']"
        );
        //set focus
        $pager->click();
        //clear field
        $this->clearInput($pager);
        $pager->value($page);
        //simulate lost focus
        $this->test->keysSpecial('enter');
        $this->waitForAjax();
        $pagerLabel->click();
        $this->waitForAjax();
        return $this;
    }

    /**
     * Navigate to the next page
     *
     * @return $this
     */
    public function nextPage()
    {
        $this->test->byXPath("{$this->gridPath}//div[contains(@class,'pagination')]//a[contains(.,'Next')]")->click();
        $this->waitForAjax();
        return $this;
    }

    /**
     * Navigate to the previous page
     *
     * @return $this
     */
    public function previousPage()
    {
        $this->test->byXPath("{$this->gridPath}//div[contains(@class,'pagination')]//a[contains(.,'Prev')]")->click();
        $this->waitForAjax();
    }

    /**
     * Get the current page number
     *
     * @return int
     */
    public function getCurrentPageNumber()
    {
        return intval($this->test->byXPath("{$this->gridPath}//div[contains(@class,'pagination')]/ul//input")->value());
    }

    /**
     * Get pages count by parsing text label
     *
     * @return int
     */
    public function getPagesCount()
    {
        $pager = $this->test->byXPath("{$this->gridPath}//div[contains(@class,'pagination')]//label[@class='dib'][2]")
            ->text();
        preg_match('/of\s+(\d+)/i', $pager, $result);
        return intval($result[1]);
    }

    /**
     * Get records count in grid by parsing text label
     *
     * @return int
     */
    public function getRowsCount()
    {
        $pager = $this->test->byXPath("{$this->gridPath}//div[contains(@class,'pagination')]//label[@class='dib'][3]")
            ->text();
        preg_match('/of\s+(\d+)/i', $pager, $result);
        return intval($result[1]);
    }

    /**
     * Get all elements from data grid
     *
     * @param null|int $id
     * @return array PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function getRows($id = null)
    {
        if (is_null($id)) {
            $records = $this->test
                ->elements($this->test->using('xpath')->value("{$this->gridPath}//table/tbody/tr"));
        } else {
            $records = $this->test
                ->elements($this->test->using('xpath')->value("{$this->gridPath}//table/tbody/tr[{$id}]"));
        }

        return $records;
    }

    /**
     * Verify entity exist on the current page
     *
     * @param array $entityData
     * @return bool
     */
    public function entityExists($entityData)
    {
        $xpath = '';
        foreach ($entityData as $entityField) {
            if ($xpath != '') {
                $xpath .= " and ";
            }
            $xpath .=  "td[contains(.,'{$entityField}')]";
        }
        $xpath = "{$this->gridPath}//table/tbody/tr[{$xpath}]";
        return $this->isElementPresent($xpath);
    }

    /**
     * Verify entity exist on the current page
     *
     * @param array $entityData
     * @param null|int $column
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function getEntity($entityData, $column = null)
    {
        $xpath = '';
        foreach ($entityData as $entityField) {
            if ($xpath != '') {
                $xpath .= " and ";
            }
            $xpath .=  "td[contains(.,'{$entityField}')]";
        }
        $postFix = "";
        if (!is_null($column)) {
            $postFix = "/td[{$column}]";
        }

        $xpath = "{$this->gridPath}//table/tbody/tr[{$xpath}]{$postFix}";
        return $this->test->byXPath($xpath);
    }

    /**
     * @param array  $entityData
     * @param string $actionName Default is Delete
     * @param bool   $confirmation
     *
     * @return $this
     */
    public function deleteEntity($entityData = array(), $actionName = 'Delete', $confirmation = true)
    {
        return $this->action($entityData, $actionName, $confirmation);
    }

    /**
     * @param array  $entityData
     * @param string $actionName Default is Delete
     * @param bool   $confirmation
     *
     * @return $this
     */
    public function action($entityData = array(), $actionName = 'Update', $confirmation = false)
    {
        $entity = $this->getEntity($entityData);
        $element = $entity->element(
            $this->test->using('xpath')->value("td[@class = 'action-cell']//a[contains(., '...')]")
        );
        // hover will show menu, 1st click - will hide, 2nd - will show again
        $element->click();
        $element->click();

        $entity->element(
            $this->test->using('xpath')->value("td[@class = 'action-cell']//a[contains(., '{$actionName}')]")
        )->click();
        if ($confirmation) {
            $this->test->byXPath("//div[div[contains(., 'Delete Confirmation')]]//a[contains(., 'Yes')]")->click();
        }

        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * Get grid headers
     *
     * @return array PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function getHeaders()
    {
        $records = $this->test->elements(
            $this->test->using('xpath')
                ->value("{$this->gridPath}//table/thead/tr/th[not(contains(@style, 'display: none;'))]")
        );
        return $records;
    }

    /**
     * Get column number by hedaer name
     *
     * @param string $headerName
     * @return int
     */
    public function getColumnNumber($headerName)
    {
        $records = $this->getHeaders();
        $i = 0;
        $found = 0;
        foreach ($records as $column) {
            /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $column */
            $name = $column->text();
            $i++;
            if (strtoupper($headerName) == strtoupper($name)) {
                $found = $i;
                break;
            }
        }
        return $found;
    }

    /**
     * Get grid column data
     *
     * @param int $columnId
     * @return array
     */
    public function getColumn($columnId)
    {
        $columnData = $this->test->elements(
            $this->test->using('xpath')
                ->value("{$this->gridPath}//table/tbody/tr/td[not(contains(@style, 'display: none;'))][{$columnId}]")
        );
        $rowData = array();
        foreach ($columnData as $value) {
            /** @var \PHPUnit_Extensions_Selenium2TestCase_Element $value */
            $rowData[] = $value->text();
        }
        return $rowData;
    }

    /**
     * @param $columnName
     * @param string $order DESC or ASC
     * @return $this
     */
    public function sortBy($columnName, $order = '')
    {
        //get current state descending or ascending
        switch (strtolower($order)) {
            case 'desc':
                $orderFull = 'descending';
                break;
            case 'asc':
                $orderFull = 'ascending';
                break;
            default:
                $orderFull = $order;
        }

        //get current sort order status
        $current = $this->test->byXPath("{$this->gridPath}//table/thead/tr/th[a[contains(., '{$columnName}')]]")
            ->attribute('class');
        if (strpos($current, $orderFull) === false || $order == '') {
            $this->test->byXPath("{$this->gridPath}//table/thead/tr/th/a[contains(., '{$columnName}')]")->click();
            $this->waitForAjax();
            if ($order != '') {
                return $this->sortBy($columnName, $order);
            }
        }
        return $this;
    }

    /**
     * Change page size
     *
     * @param string|int $pageSize
     * @return $this
     */
    public function changePageSize($pageSize)
    {
        $this->test->byXPath("{$this->gridPath}//div[@class='page-size pull-right form-horizontal']//button")->click();
        if (is_integer($pageSize)) {
            $this->test->byXPath(
                "{$this->gridPath}//div[@class='page-size pull-right form-horizontal']" .
                "//ul[contains(@class,'dropdown-menu')]/li/a[text() = '{$pageSize}']"
            )->click();
        } elseif (is_string($pageSize)) {
            $command = '';
            switch (strtolower($pageSize)) {
                case 'last':
                    $command = "last()";
                    break;
                case 'first':
                    $command = "1";
                    break;
            }
            $xpath = "{$this->gridPath}//div[@class='page-size pull-right form-horizontal']" .
                "//ul[contains(@class,'dropdown-menu')]/li[{$command}]/a";
            $this->test->byXPath($xpath)->click();
        }

        $this->waitForAjax();
        return $this;
    }

    /**
     * @param string $message Message to verify
     *
     * @return $this
     */
    public function assertNoDataMessage($message)
    {
        PHPUnit_Framework_Assert::assertTrue(
            $this->isElementPresent("//div[@class='no-data']/span[contains(., '{$message}')]")
        );
        return $this;
    }
}
