<?php
/** Prepared-statement adapter used only by new PM code. */
class PmDb {
    private mysqli $connection;

    public function __construct($database) {
        if (!isset($database->connection) || !($database->connection instanceof mysqli)) {
            throw new RuntimeException('PM database connection is unavailable.');
        }
        $this->connection = $database->connection;
    }

    public function fetchOne(string $sql, string $types = '', array $params = []): ?array {
        $rows = $this->fetchAll($sql, $types, $params);
        return $rows[0] ?? null;
    }

    public function fetchAll(string $sql, string $types = '', array $params = []): array {
        $statement = $this->prepareAndExecute($sql, $types, $params);
        $result = $statement->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $statement->close();
        return $rows;
    }

    public function execute(string $sql, string $types = '', array $params = []): int {
        $statement = $this->prepareAndExecute($sql, $types, $params);
        $affectedRows = $statement->affected_rows;
        $statement->close();
        return $affectedRows;
    }

    private function prepareAndExecute(string $sql, string $types, array $params): mysqli_stmt {
        if (strlen($types) !== count($params)) {
            throw new InvalidArgumentException('Prepared parameter count does not match type count.');
        }
        $statement = $this->connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException('Unable to prepare PM database query.');
        }
        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Unable to execute PM database query.');
        }
        return $statement;
    }
}
