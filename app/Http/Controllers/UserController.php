<?php

namespace App\Http\Controllers;

use App\Enums\HttpStatusCodes;
use App\Facades\AuthenticationFacade;
use App\Facades\FollowFacade;
use App\Facades\UsersFacade;
use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\AffirmationResource;
use App\Http\Resources\DiscussionResource;
use App\Http\Resources\FollowResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UnfollowResource;
use App\Http\Resources\UserFullResource;
use App\Http\Resources\UserOneTimeTokenResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserSchoolResource;
use App\Models\Affirmation;
use App\Models\Discussion;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Models\UserImage;
use App\Models\UserSchool;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    
    public function index()
    {
        return UserResource::collection(User::orderBy('created_at', 'desc')->paginate(25));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */


    public function update(Request $request)
    {
        $user = User::where('id', $request->user()->id)->first();

        if(!$user){
            return response()->json(['User does not exist'], 404);
        }

        $input = $request->all();

        if(isset($input['email'])) {
            unset($input['email']);
        }

        if(isset($input['password'])) {
            unset($input['password']);
        }

        if(isset($input['avatar'])) {
            unset($input['avatar']);
        }



        $data = $input + $user->toArray();

        $valid = $user->validate($data, ['email', 'password', 'username', 'avatar'], ['username' => Rule::unique('users')->ignore($user->id), 'email' => Rule::unique('users')->ignore($user->id)]);

        if (!$valid) {
            return response()->json($user->getValidationErrors(), 422);
        }

        // For some weird reason, I have to get a new uer object.
        
        $user = User::where('id', $request->user()->id)->first();

        //return response()->json($data, 422);
        $user->update($input + $user->toArray());

        return UserResource::make($user);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function toggleFollow(Request $request, $id) {

        $following = User::where('id', $id)->first();

        $follower = FollowFacade::toggleFollowing($following, $request->user());

        if($follower){
            return new FollowResource($follower);
        }

        return  new UnfollowResource($following);
    }

    public function profile(Request $request, $id) {

        $user = User::where('id', $id)->first();

        return UserFullResource::make($user);
    }


  public function me(Request $request) {

    $user = $request->user();

    if(!$user){
        return response()->json(['Unauthorized'], 401);
    }

    $user = User::where('id', $user->id)->first();

    return UserFullResource::make($user);
  }

  public function onetimetoken(Request $request) {

    $user = User::where('id', $request->user()->id)->first();

    $user = AuthenticationFacade::createOneTimeLoginToken($user);

    return UserOneTimeTokenResource::make($user);
  }

  public function uploadAvatarImage(StoreImageRequest $request)
   {
       /*$this->validate($request, [
            'image' => 'required|mimes:png,jpg,gif|max:9999',
        ]);*/

        $user = $request->user();

        if(!$user){
            return response()->json(['Unauthorized'], 401);
        }

        $base_location = 'images';

        // Handle File Upload
        if($request->hasFile('image')) {              
            //Using store(), the filename will be hashed. You can use storeAs() to specify a name.
            //To specify the file visibility setting, you can update the config/filesystems.php s3 disk visibility key,
            //or you can specify the visibility of the file in the second parameter of the store() method like:
            //$imagePath = $request->file('document')->store($base_location, ['disk' => 's3', 'visibility' => 'public']);
            
            $imagePath = $request->file('image')->store($base_location, ['disk' => 's3', 'visibility' => 'public']);
          
        } else {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }
    
        //We save new path
        $user->forceFill([
            'avatar' => $imagePath
        ]);

        $user->save();
       
        return UserFullResource::make($user);
    }

    public function uploadBannerImage(StoreImageRequest $request)
    {
       /*$this->validate($request, [
            'image' => 'required|mimes:png,jpg,gif|max:9999',
        ]);*/

        $user = $request->user();

        if(!$user){
            return response()->json(['Unauthorized'], 401);
        }

        $base_location = 'images';

        // Handle File Upload
        if($request->hasFile('image')) {              
            //Using store(), the filename will be hashed. You can use storeAs() to specify a name.
            //To specify the file visibility setting, you can update the config/filesystems.php s3 disk visibility key,
            //or you can specify the visibility of the file in the second parameter of the store() method like:
            //$imagePath = $request->file('document')->store($base_location, ['disk' => 's3', 'visibility' => 'public']);
            
            $imagePath = $request->file('image')->store($base_location, ['disk' => 's3', 'visibility' => 'public']);
          
        } else {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }
    
        //We save new path
        $user->forceFill([
            'banner_image' => $imagePath
        ]);

        $user->save();
       
        return UserFullResource::make($user);
    }

    public function createDonationPage(StoreImageRequest $request) {

        $user = $request->user();

        if(!$user){
            return response()->json(['Unauthorized'], 401);
        }

        if(!$user->stripe_express_account_id) {
            return response()->json(['Must be authenticated with Stripe first.'], HttpStatusCodes::HTTP_NO_CONTENT);
        }

        UsersFacade::runAllDonationLinkCreation($user);

        return UserFullResource::make($user);

    }

}
