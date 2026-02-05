<?php
/**
 * Controller de Tickets - Sistema de soporte usuario
 */

class TicketController extends Controller
{
    private Ticket $ticketModel;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
    }

    /**
     * Lista de tickets del usuario
     */
    public function index(): void
    {
        Auth::require();

        if (!(new Setting())->get('support_enabled', true)) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        $tickets = $this->ticketModel->getByUser(Auth::id());

        $this->render('tickets/index', [
            'tickets' => $tickets,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Crear nuevo ticket
     */
    public function create(): void
    {
        Auth::require();

        if (!(new Setting())->get('support_enabled', true)) {
            $this->redirect('index.php?action=dashboard');
            return;
        }

        if ($this->isPost()) {
            $this->processCreate();
            return;
        }

        $this->render('tickets/create', [
            'csrf_token' => $this->generateCsrf(),
            'flash' => $this->getFlash()
        ]);
    }

    private function processCreate(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token de seguridad inválido');
            $this->redirect('index.php?action=ticket_create');
            return;
        }

        $subject = trim($this->post('subject', ''));
        $message = trim($this->post('message', ''));

        if (empty($subject) || empty($message)) {
            $this->flash('error', 'El asunto y el mensaje son obligatorios');
            $this->redirect('index.php?action=ticket_create');
            return;
        }

        if (strlen($subject) > 255) {
            $this->flash('error', 'El asunto no puede superar 255 caracteres');
            $this->redirect('index.php?action=ticket_create');
            return;
        }

        $this->ticketModel->createTicket(Auth::id(), $subject, $message);

        $this->flash('success', 'Ticket creado correctamente. El administrador lo revisará pronto.');
        $this->redirect('index.php?action=tickets');
    }
}
