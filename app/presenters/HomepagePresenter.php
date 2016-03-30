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
            SELECT AVG(pos) * 100 as pos_avg, AVG(neu) * 100 as neu_avg, AVG(neg) * 100 as neg_avg, COUNT(*) as total_count, user
            FROM comments
            GROUP BY user
            HAVING total_count > 5
            ORDER BY count(*) DESC
        ";

        $this->template->users = $this->db->query($query)->fetchAll();
	}

}
