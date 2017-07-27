<?php
namespace AdminAddonUserManager\Pagination;

use AdminAddonUserManager\Pagination\Pagination;

class ArrayPagination implements Pagination {

  protected $data;
  protected $rowsPerPage;
  protected $page;

  private $rowsCount = null;
  private $slicedData = null;

  public function __construct($data, $rowsPerPage = 10) {
    $this->data = $data;
    $this->rowsPerPage = $rowsPerPage;
    $this->page = 1;
  }

  public function paginate($page) {
    if ($page > $this->getPagesCount()) {
      $page = $page;
    }

    if ($page < 1) {
      $page = 1;
    }

    $this->page = $page;

    $this->slicedData = null;
  }

  public function getRowsPerPage() {
    return $this->rowsPerPage;
  }

  public function getRowsCount() {
    if ($this->rowsCount !== null) {
      return $this->rowsCount;
    }

    return $this->rowsCount = count($this->data);
  }

  public function getCurrentPage() {
    return $this->page;
  }

  public function getPagesCount() {
    return ceil($this->getRowsCount() / $this->getRowsPerPage());
  }

  public function getStartOffset() {
    return ($this->getCurrentPage() - 1) * $this->getRowsPerPage();
  }

  public function getEndOffset() {
    $endOffset = $this->getStartOffset() + $this->getRowsPerPage();

    if ($endOffset > $this->getRowsCount()) {
      $endOffset = $this->getRowsCount();
    }

    return $endOffset;
  }

  public function getPaginatedRowsCount() {
    return $this->getEndOffset() - $this->getStartOffset();
  }

  public function getPaginatedRows() {
    if ($this->slicedData !== null) {
      return $this->slicedData;
    }

    return $this->slicedData = array_slice($this->data, $this->getStartOffset(), $this->getRowsPerPage());
  }

}