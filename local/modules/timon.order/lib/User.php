<?php
namespace Timon\Order;
use Bitrix\Main\Engine\Controller;

class User extends Controller
{
    public function getUsersAction(array $filter): array
    {
        $result = [];
        $users = \CUser::GetList();
        while ($user = $users->Fetch()) {
            $result[] = $user;
        }

        return $result;
    }
}
