<?php

namespace MaarchCourrier\SignatureBook\Domain\Problem;

use MaarchCourrier\Core\Domain\Problem\Problem;

class UserInfoRetrievalFailedProblem extends Problem
{
    public function __construct()
    {
        parent::__construct(
            _RETRIEVAL_USER_INFO_FAILED,
            500,
            lang: 'signatoryBookUserInfoRetrievalFailed'
        );
    }
}
