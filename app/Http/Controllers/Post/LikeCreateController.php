<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\LikeCreateRequest;
use App\Models\Post;
use App\Models\PostLike;
use App\Services\PostService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LikeCreateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LikeCreateRequest $request, PostService $postService)
    {

        // ログインしていなかったら、例外をスロー
        if (null === $request->user()){
            throw new AccessDeniedHttpException();
        }
        
        // userIdとpostIdを取得
        $userId = $request->user()->id;
        $postId = $request->route('id');

        // userIdとpostIdから、対象のPostLikeモデルを取得
        $postLike = $postService->getPostLike($postId, $userId);
        
        // いいねをつける個数を取得
        $addLikes = $request->input('add-likes');

        // いいねを保存する処理
        // ログインユーザーが対象postに初めていいねする場合
        if ($postLike === null){
            $postLike = new PostLike;
            $postLike->user_id = $userId;   // user_idカラムの値を保存
            $postLike->post_id = $postId;   // post_idカラムの値を保存
            $postLike->num_of_likes = $addLikes;   // num_of_likesカラムの値を保存
            $postLike->save();
        }
        // いいねするのが初めてではなかった場合（追加でいいねをする場合）
        elseif ($postLike !== null){
            $postLike->num_of_likes += $addLikes;

            // 合計のいいね数が5を超える場合は、いいねを保存せず投稿詳細画面へ戻す
            if ($postLike->num_of_likes > 5){
                return redirect()->route('post.detail', ['id' => $postId])
                    ->with('feedback.error', "いいねは１投稿につき最大5件までです");
            }
            $postLike->save();
        }


        // postsテーブルのnum_of_likesカラムにもいいね数を保存する
        $post = Post::where('id', $postId)->firstOrFail();
        $post->num_of_likes += $addLikes;
        $post->save();

        return redirect()->route('post.detail', ['id' => $postId])
            ->with('feedback.success', "あなたのいいねが正常に保存されました");
    }
}
