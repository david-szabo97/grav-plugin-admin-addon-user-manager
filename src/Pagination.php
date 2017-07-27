<?php
namespace AdminAddonUserManager\Pagination;

interface Pagination {

  public function paginate($page);

  public function getRowsPerPage();
  public function getRowsCount();
  public function getCurrentPage();
  public function getPagesCount();
  public function getStartOffset();
  public function getEndOffset();
  public function getPaginatedRowsCount();
  public function getPaginatedRows();

}