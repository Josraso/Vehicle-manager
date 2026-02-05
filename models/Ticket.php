<?php
/**
 * Model Ticket - Sistema de soporte simple
 */

class Ticket extends Model
{
    protected string $table = 'tickets';

    public function createTicket(int $userId, string $subject, string $message): int
    {
        return $this->create([
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message
        ]);
    }

    public function getByUser(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return $this->query($sql, [$userId]);
    }

    public function getAllTickets(string $status = '', int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 't.status = ?';
            $params[] = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM {$this->table} t {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT t.*, u.name as user_name, u.email as user_email
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                {$whereClause}
                ORDER BY t.status ASC, t.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage))
        ];
    }

    public function reply(int $ticketId, string $reply, bool $close = false): bool
    {
        $data = [
            'admin_reply' => $reply,
            'replied_at' => date('Y-m-d H:i:s')
        ];
        if ($close) {
            $data['status'] = 'closed';
        }
        return $this->update($ticketId, $data);
    }

    public function setStatus(int $ticketId, string $status): bool
    {
        return $this->update($ticketId, ['status' => $status]);
    }

    public function countOpen(): int
    {
        return $this->count('status', 'open');
    }
}
