<?php

namespace Gianism\Chat;


class Thread extends Util
{

    protected function __construct( array $argument = array() ){

    }

    /**
     * Returns thread
     *
     * @param int $user_id
     * @param int $paged
     * @return array
     */
    public function get_all($user_id, $paged = 1){
        $paged = max(1, $paged);
        $query = <<<EOS
            SELECT t.* FROM {$this->thread_table} AS t
            INNER JOIN {$this->contact_table} AS c
            USING (thread_id)
            WHERE c.user_id = %d
            ORDER BY t.updated DESC
            LIMIT %d, %d
EOS;
        return $this->get_results($query, $user_id, ($paged - 1) * 20, 20);
    }

    public function render_list( \stdClass $thread ){
        $latest = $this->get_chats($thread->thread_id, 1, 1);
        $members = $this->get_thread_member($thread->thread_id);
        $html = '';
        foreach( $latest as $message ){
            $avatars = '';
            $user_names = array();
            $counter = 0;
            $size = count($members) > 1 ? 48 : 96;
            foreach($members as $member){
                $counter++;
                if($counter < 5){
                    $avatars .= get_avatar($member->ID, $size);
                }
                $user_names[] = esc_html($member->display_name);
            }
            $user_names = implode(', ', $user_names);
            $time = $this->passed_time($message->created);
            $url = $this->thread_url($thread->thread_id);
            $body = esc_html($message->message);
            $html .= <<<EOS
                <li class="wpg-message">
                    <a href="{$url}">
                    <div class="avatars">{$avatars}</div>
                    <span class="time">{$time}</span>
                    <p class="message">
                        <strong>{$user_names}</strong>
                        <span>{$body}</span>
                    </p>
                    </a>
                </li>
EOS;
        }
        return $html;
    }

    /**
     * Render chat list
     *
     * @param \stdClass $chat
     * @param int $user_id
     * @return string
     */
    public function render_chat( \stdClass $chat, $user_id = 0){
        if(!$user_id){
            $user_id = $this->id;
        }
        $time = $this->passed_time($chat->created);
        if( $user_id == $chat->user_id ){
            $is_owner = ' me';
            $name = $this->_('You');
        }else{
            $is_owner = '';
            $name = esc_html($chat->display_name);
        }

        $message = $this->parse_message($chat->message);
        $avatar = get_avatar($chat->user_id, 96);
        return <<<EOS
            <li data-chat-id="{$chat->chat_id}" data-chat-owner="{$chat->user_id}" class="chat-block{$is_owner}">
                <div class="profile">
                    {$avatar}
                    <cite>{$name}</cite>
                </div>
                <span class="time">{$time}</span>
                <p class="body">{$message}</p>
            </li>
EOS;
    }

    /**
     * Apply filter to message text
     *
     * @param string $message
     * @return mixed|string|void
     */
    private function parse_message($message){
        return nl2br(esc_html($message));
    }


    /**
     * Display time
     *
     * @param string $date_time
     * @return string
     */
    private function passed_time($date_time){
        list($cur_year, $cur_month, $cur_day, $cur_date, $cur_hour, $cur_minute) = explode('-', date_i18n('Y-n-j-D-H-i'));
        list($year, $month, $day, $date, $hour, $minute) = explode('-', mysql2date('Y-n-j-D-H-i', $date_time));
        $passed = '';
        if($cur_year != $year || $cur_month != $month || $cur_day != $day){
            return mysql2date(get_option('date_format'), $date_time);
        }else{
            return mysql2date(get_option('time_format'), $date_time);
        }
    }

    /**
     * Get thread member
     *
     * @param int $thread_id
     * @param bool $exclude_me Exclude current user. Defautl true
     * @return array Consist user object
     */
    public function get_thread_member( $thread_id, $exclude_me = true ){
        $query = <<<EOS
            SELECT u.* FROM {$this->contact_table} AS c
            LEFT JOIN {$this->db->users} AS u
            ON c.user_id = u.ID
            WHERE c.thread_id = %d
              AND (c.approved > c.ejected)
EOS;
        if($exclude_me){
            $query .= 'AND u.ID != %d';
            $query = $this->db->prepare($query, $thread_id, $this->id);
        }else{
            $query = $this->db->prepare($query, $thread_id);
        }
        return $this->get_results($query, $thread_id);
    }

    /**
     * Get message
     *
     * @param int $thread_id
     * @param int $paged
     * @param int $per_page
     * @return array
     */
    public function get_chats($thread_id, $paged = 1, $per_page = 10){
        $paged = max(1, $paged);
        $query = <<<EOS
            SELECT c.*, u.display_name FROM {$this->chat_table} AS c
            LEFT JOIN {$this->db->users} AS u
            ON c.user_id = u.ID
            WHERE c.thread_id = %d
            ORDER BY created DESC
            LIMIT %d, %d
EOS;
        return $this->get_results($query, $thread_id, ($paged - 1) * $per_page, $per_page);
    }

    /**
     * Returns thread's chat messages
     *
     * @param int $thread_id
     * @param int $base_id The chat id from which data will retrieve
     * @param bool $older Older or newer. If true, grep older. Default false.
     * @param int $per_page Default 5
     * @return array
     */
    public function get_more($thread_id, $base_id, $older = false, $per_page = 5){
        $operand = $older ? '<' : '>';
        $query = <<<EOS
            SELECT c.*, u.display_name FROM {$this->chat_table} AS c
            LEFT JOIN {$this->db->users} AS u
            ON c.user_id = u.ID
            WHERE c.thread_id = %d AND c.chat_id {$operand} %d
            ORDER BY c.created DESC
EOS;
        if($per_page){
            $query .= ' LIMIT '.intval(max(1, $per_page));
        }
        return $this->get_results($query, $thread_id, $base_id);
    }

    /**
     * Add chat message
     *
     * @param string $message
     * @param int $thread_id
     * @param int $user_id
     * @return null|\stdClass
     */
    public function add_chat($message, $thread_id, $user_id = 0){
        if(!$user_id){
            $user_id = $this->id;
        }
        if( $this->db->insert($this->chat_table, array(
            'thread_id' => $thread_id,
            'user_id' => $user_id,
            'message' => $message,
        ), array('%d', '%d', '%s'))){
            $this->db->update($this->thread_table, array(
                'updated' => current_time('mysql'),
            ), array(
                'thread_id' => $thread_id,
            ), array('%s'), array('%d'));
            return $this->get_row("SELECT * FROM {$this->chat_table} WHERE chat_id = %d", $this->db->insert_id);
        }else{
            return null;
        }
    }

    /**
     * Start thread
     *
     * @param int $user_id
     * @param int $with
     * @return int
     */
    public function start($user_id, $with = 0){
        if(!$with){
            $with = $this->id;
        }
        $thread_id = $this->thread_exists($user_id, $with);
        if(!$thread_id){
            $this->db->insert($this->thread_table, array(
                'updated' => current_time('mysql')
            ), array('%s'));
            $thread_id = $this->db->insert_id;
            $this->add_contact($thread_id, $user_id, true);
            $this->add_contact($thread_id, $with, true);
        }
        return $thread_id;
    }

    /**
     * Add contact list
     *
     * @param int $thread_id
     * @param int $user_id
     * @param bool $approved
     * @return bool
     */
    public function add_contact($thread_id, $user_id, $approved = true){
        return (bool)$this->db->insert($this->contact_table, array(
            'thread_id' => $thread_id,
            'user_id' => $user_id,
            'approved' => $approved ? current_time('mysql') : '0000-00-00 00:00:00'
        ), array('%d', '%d', '%s'));
    }

    /**
     * Returns thread id if exists
     *
     * @param int $who
     * @param int $with
     * @return int thread id
     */
    private function thread_exists($who, $with){
        $query = <<<EOS
            SELECT thread_id
            FROM {$this->contact_table}
            WHERE user_id IN (%d, %d)
              AND COUNT(user_id) = 2
            GROUP BY thread_id
EOS;
        return (int)$this->get_var($query, $who, $with);
    }

    public function invite($thread_id, $user_id){

    }

    /**
     * Detect if user can post message
     *
     * @param int $user_id
     * @param int $thread_id
     * @return bool
     */
    public function is_allowed($user_id, $thread_id){
        $query = <<<EOS
            SELECT (approved > ejected) FROM {$this->contact_table}
            WHERE thread_id = %d AND user_id = %d
EOS;
        return (bool)$this->get_var($query, $thread_id, $user_id);
    }
} 