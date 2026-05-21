<?php

namespace Truschery\Idem\Enums;

enum LockState
{
    case ACQUIRED;
    case COMPLETED;
    case LOCKED;

}