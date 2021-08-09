<?php

namespace Battis\OctoPrintPool;

class POSIX
{
    // 16 bits
    // bits 0-3: file type 0xF000
    // bits 4-6: sticky 0x0E00
    // bits 7-9: owner 0x01C0
    // bits 10-12: group 0x0038
    // bits 13-15: world 0x0007

    const FILE_TYPE_MASK = 0xF000;

    const FILE_TYPE_SOCKET = 0xC000;
    const FILE_TYPE_SYMBOLIC_LINK = 0xA000;
    const FILE_TYPE_REGULAR = 0x8000;
    const FILE_TYPE_BLOCK_SPECIAL = 0x6000;
    const FILE_TYPE_DIRECTORY = 0x4000;
    const FILE_TYPE_CHARACTER_SPECIAL = 0x2000;
    const FILE_TYPE_FIFO_PIPE = 0x1000;

    const FILE_TYPE_ABBREVIATIONS = [
        self::FILE_TYPE_SOCKET => 's',
        self::FILE_TYPE_SYMBOLIC_LINK => 'l',
        self::FILE_TYPE_REGULAR => 'r',
        self::FILE_TYPE_BLOCK_SPECIAL => 'b',
        self::FILE_TYPE_DIRECTORY => 'd',
        self::FILE_TYPE_CHARACTER_SPECIAL => 'c',
        self::FILE_TYPE_FIFO_PIPE => 'p'
    ];

    const OWNER_READ = 0x0100;
    const OWNER_WRITE = 0x0080;
    const OWNER_EXECUTE = 0x0040;
    const OWNER_STICKY = 0x0800;
    const OWNER_SETUIID = self::OWNER_STICKY;
    const OWNER_SETGID = self::OWNER_STICKY;

    const GROUP_READ = 0x0020;
    const GROUP_WRITE = 0x0010;
    const GROUP_EXECUTE = 0x0008;
    const GROUP_STICKY = 0x0400;
    const GROUP_SETUID = self::GROUP_STICKY;
    const GROUP_SETGID = self::GROUP_STICKY;

    const WORLD_READ = 0x0004;
    const WORLD_WRITE = 0x0002;
    const WORLD_EXECUTE = 0x0001;
    const WORLD_STICKY = 0x0200;
    const WORLD_SETUID = self::WORLD_STICKY;
    const WORLD_SETGID = self::WORLD_STICKY;

    private static function bit($permissions, $mask)
    {
        $match = '?';
        $unmatch = '-';
        if ($mask & (POSIX::OWNER_READ | POSIX::GROUP_READ | POSIX::WORLD_READ)) {
            $match = 'r';
        }
        if ($mask & (POSIX::OWNER_WRITE | POSIX::GROUP_WRITE | POSIX::WORLD_WRITE)) {
            $match = 'w';
        }
        if ($mask & (POSIX::OWNER_EXECUTE | POSIX::GROUP_EXECUTE | POSIX::WORLD_EXECUTE)) {
            if ($permissions & (POSIX::OWNER_STICKY | POSIX::GROUP_STICKY | POSIX::WORLD_STICKY)) {
                $match = 's';
                $unmatch = 'S';
            } else {
                $match = 'x';
            }
        }
        return $permissions & $mask ? $match : $unmatch;
    }

    /**
     * @param string $path
     * @return string
     * @see https://www.php.net/manual/en/function.fileperms.php
     */
    public static function symbolic_fileperms(string $path, $permissions = false): string
    {
        if (!$permissions) {
            $permissions = fileperms($path);
        }
        $symbolic = '-'; // unknown file type;
        foreach (self::FILE_TYPE_ABBREVIATIONS as $mask => $abbreviation) {
            if ($permissions & self::FILE_TYPE_MASK === $abbreviation) {
                $symbolic = $abbreviation;
                break;
            }
        }

        for ($i = self::OWNER_READ; $i >= self::WORLD_EXECUTE; $i /= 0x0002) {
            $symbolic .= self::bit($permissions, $i);
        }

        return $symbolic;
    }

    public static function symbolic_chmod(string $path, string $permissions)
    {
        $calculated = fileperms($path) & ~self::FILE_TYPE_MASK;
        if (preg_match('/[rwx\-]{9}/', $permissions)) {
            // straight permissions list
        } else {
            if (preg_match_all('/([ugoa]*)([+\-=])([rwxXst]+)/', $permissions, $terms, PREG_SET_ORDER)) {
                $USERS = 1;
                $OPERATION = 2;
                $PERMISSIONS = 3;
                foreach ($terms as $term) {
                    if (preg_match('/[Xst]/', $term[$PERMISSIONS])) {
                        // TODO deal with fancy perms
                        return false;
                    }
                    if (!empty($term[$PERMISSIONS])){
                        $rwx = 00;
                        foreach (['r' => 04, 'w' => 02, 'x' => 01] as $symbol => $bit) {
                            if (str_contains($term[$PERMISSIONS], $symbol)) {
                                $rwx += $bit;
                            }
                        }
                        if ($term[$USERS] === 'a' || $term[$USERS] === '') {
                            $term[$USERS] = 'ugo';
                        }
                        foreach (['u' => 0100, 'g' => 0010, 'o' => 0001] as $user => $position) {
                            if (str_contains($term[$USERS], $user)) {
                                switch ($term[$OPERATION]) {
                                    case '+':
                                        $mask = $rwx * $position;
                                        $calculated = $calculated | $mask;
                                        break;
                                    case '-':
                                        $mask = ~($rwx * $position);
                                        $calculated = $calculated & $mask;
                                        break;
                                    case '=':
                                        $calculated = $calculated & ~(07 * $position) | ($rwx * $position);
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return chmod($path, $calculated);
    }
}
