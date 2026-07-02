<?php

use MMAE\ApiResponse\tests\TestCase;
use MMAE\ApiResponse\Traits\HasApiResponse;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

uses(HasApiResponse::class)->in('Unit/ResponseTest.php');
