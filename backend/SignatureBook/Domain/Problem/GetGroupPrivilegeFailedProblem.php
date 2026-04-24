<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class GetGroupPrivilegeFailedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(_FAILED_GET_GROUP_PRIVILEGES_SIGNATORY_BOOK . " :  ", 403);
    }
}
