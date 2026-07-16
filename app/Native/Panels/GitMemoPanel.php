<?php declare(strict_types=1);
namespace App\Native\Panels;
use App\Native\Panel;
use Yangweijie\Ui2\Layout\LayoutNode;
use Yangweijie\Ui2\Rendering\WidgetRenderer\LabelSpec;
use Yangweijie\Ui2\Widgets\ScrollViewControl;
use Yangweijie\Ui2\Widgets\Surface;

final class GitMemoPanel implements Panel
{
    public function build(Surface $surface, string $key, float $width, float $height): LayoutNode
    {
        $w = $width - 48;
        $items = [
            ['Basic', 0],
            ['git init', 1], ['git clone <url>', 1], ['git add <file>', 1],
            ['git commit -m "msg"', 1], ['git push', 1], ['git pull', 1],
            ['Branch', 0],
            ['git branch', 1], ['git checkout -b <name>', 1],
            ['git merge <branch>', 1], ['git rebase <branch>', 1],
            ['Stash', 0],
            ['git stash', 1], ['git stash pop', 1], ['git stash list', 1],
            ['History', 0],
            ['git log --oneline', 1], ['git diff', 1], ['git blame <file>', 1],
            ['Remote', 0],
            ['git remote -v', 1], ['git fetch', 1], ['git remote add <name> <url>', 1],
            ['Undo', 0],
            ['git reset HEAD <file>', 1], ['git revert <commit>', 1],
            ['git reset --hard HEAD~1', 1],
        ];
        $children = [];
        $children[] = LayoutNode::leaf(null, new LabelSpec('Git Memo', size: 16), width: $w, height: 28);
        $totalH = 36;
        foreach ($items as [$text, $level]) {
            $opacity = $level === 0 ? 0.55 : 1.0;
            $pad = $level > 0 ? '  ' : '';
            $children[] = LayoutNode::leaf(null, new LabelSpec($pad . $text, size: 13, opacity: $opacity), width: $w, height: 18);
            $totalH += 20;
        }
        $sv = new ScrollViewControl("p:{$key}", $children, width: $width, height: $height, gap: 1, padding: 18.0, contentHeight: $totalH);
        $sv->bind($surface);
        return $sv->root();
    }
}
