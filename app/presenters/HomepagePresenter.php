<?php

namespace App\Presenters;

use Nette;


class HomepagePresenter extends Nette\Application\UI\Presenter
{

    /** @var Nette\Database\Context @inject */
    public $db;

	public function renderDefault()
	{
        $query = "
            SELECT AVG(pos) as pos_avg, AVG(neu) as neu_avg, AVG(neg) as neg_avg, COUNT(*) as total_count, user
            FROM comments
            GROUP BY user
            HAVING total_count > 5
            ORDER BY count(*) DESC
        ";

        $this->template->users = $this->db->query($query)->fetchAll();
	}

}
