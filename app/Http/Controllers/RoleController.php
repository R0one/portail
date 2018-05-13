<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Role;
use App\Models\Semester;
use App\Exceptions\PortailException;
use App\Traits\HasStages;

/**
 * Gestion des groupes utilisateurs
 *
 * @resource Role
 */
class RoleController extends Controller
{
	use HasStages;
	/**
	 * Scopes Role
	 *
	 * Les Scopes requis pour manipuler les Roles
	 */
	public function __construct() {
		$this->middleware(
			\Scopes::matchOne(
				['user-get-groups-enabled', 'user-get-groups-disabled'],
				['client-get-groups-enabled', 'client-get-groups-disabled']
			),
			['only' => ['index', 'show']]
		);
		$this->middleware(
			array_merge(
				\Scopes::matchOne(
					['user-manage-groups']
				), [
					'admin',
				]
			),
			['only' => ['store', 'update', 'destroy']]
		);
	}

	protected function checkOnlyFor($only_for) {
		@list($tableName, $id) = explode('-', $only_for);
		$class = '\\App\\Models\\'.studly_case(str_singular($tableName));

		if (str_singular($tableName) === $tableName || !class_exists($class))
			abort(404, 'La table donnée n\'existe pas !');
		else if (!method_exists($class, 'roles'))
			abort(400, 'La table donnée ne possède pas de roles');
		else if ($id && !resolve($class)->find($id))
			abort(400, 'L\'id associé à la table donnée n\'existe pas');

		return $only_for;
	}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $ressource_type = null, $ressource_id = null) {
		$inputs = $request->input();
		// On vérifie que la ressource possède des rôles
		if ($ressource_type || isset($inputs['only_for']))
			$inputs['only_for'] = $this->checkOnlyFor($ressource_type ? $ressource_type.($ressource_id ? '-'.$ressource_id : '') : $inputs['only_for']);

		if ($request->has('stage') || $request->has('fromStage') || $request->has('toStage') || $request->has('allStages')) {
	        // On inclue les relations et on les formattent.
			unset($inputs['stage']);
			unset($inputs['fromStage']);
			unset($inputs['toStage']);
			unset($inputs['allStages']);

			$roles = $request->has('stage') ? Role::getStage($request->stage, $inputs) : Role::getStages($request->fromStage, $request->toStage, $inputs);
		}
		else {
			$roles = new Role;

			foreach ($inputs as $key => $value) {
				if (!\Schema::hasColumn($roles->getTable(), $key))
					throw new PortailException('L\'attribut "'.$key.'" n\'existe pas');

				$roles = $roles->where($key, $value);
			}

			$roles = $roles->get();
		}

		return response()->json($roles, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $ressource_type = null, $ressource_id = null) {
		$role = new Role;
		$role->type = $request->type;
		$role->name = $request->name;
		$role->description = $request->description;
		$role->limited_at = $request->limited_at;
		$role->only_for = $this->checkOnlyFor($ressource_type ? $ressource_type.($ressource_id ? '-'.$ressource_id : '') : $request->only_for);

		if ($role->save()) {
			if ($request->filled('parent_ids'))
				$role->assignParentRole($request->parent_ids);

			return response()->json($role, 201);
		}
		else
			abort(500, 'Impossible de créer le role');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $ressource_type, $ressource_id = null, $id = null) {
		// On inverse les bonnes variables
		if (is_null($id)) {
			if (is_null($ressource_id))
				list($id, $ressource_type) = [$ressource_type, $id];
			else
				list($id, $ressource_id) = [$ressource_id, $id];
		}

		$role = $request->has('withChilds') ? Role::with('childs') : new Role;
		$role = $request->has('withParents') ? $role->with('parents') : $role;

		// On vérifie que la ressource possède des rôles
		if ($ressource_type || $request->only_for)
			$role = $role->where('only_for', $this->checkOnlyFor($ressource_type ? $ressource_type.($ressource_id ? '-'.$ressource_id : '') : $request->only_for));

		$role = is_numeric($id) ? $role->find($id) : $role->where('type', $id)->first();

        if ($role) {
			$role->nbr_assigned = $role->users()->where('semester_id', Semester::getThisSemester()->id)->count();

			return response()->json($role, 200);
		}
        else
            abort(404, "Role non trouvé");
    }

	/**
	 * Update Role
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $ressource_type, $ressource_id = null, $id = null) {
		// On inverse les bonnes variables
		if (is_null($id)) {
			if (is_null($ressource_id))
				list($id, $ressource_type) = [$ressource_type, $id];
			else
				list($id, $ressource_id) = [$ressource_id, $id];
		}

		$role = $request->has('withChilds') ? Role::with('childs') : new Role;
		$role = $request->has('withParents') ? $role->with('parents') : $role;

		// On vérifie que la ressource possède des rôles
		if ($ressource_type || $request->only_for)
			$role = $role->where('only_for', $this->checkOnlyFor($ressource_type ? $ressource_type.($ressource_id ? '-'.$ressource_id : '') : $request->only_for));

		$role = is_numeric($id) ? $role->find($id) : $role->where('type', $id)->first();

		if ($request->filled('type'))
			$role->type = $request->input('type');

		if ($request->filled('name'))
			$role->name = $request->input('name');

		if ($request->filled('description'))
			$role->description = $request->input('description');

		if ($request->filled('limited_at'))
			$role->limited_at = $request->input('limited_at');

		if ($request->filled('only_for')) {
			@list($tableName, $id) = explode('-', $request->input('only_for'));
			$class = '\\App\\Models\\'.studly_case(str_singular($tableName));

			if (!class_exists($class))
				abort(404, 'La table donnée n\'existe pas !');
			else if (!in_array('App\\Traits\\HasRoles', class_uses($class)))
				abort(400, 'La table donnée ne possède pas de roles');
			else if ($id && !resolve($class)->find($id))
				abort(400, 'L\'id associé à la table donnée n\'existe pas ');

			$role->only_for = $request->input('only_for');
		}

		if ($role->save()) {
			if ($request->filled('parent_ids')) {
				$role->syncParentRole($request->parent_ids); // Attention ! Ici on change tous ses parents

				$role = $request->has('withChilds') ? $role->with('childs') : $role;
				$role = $request->has('withParents') ? $role->with('parents') : $role;
				$role = is_numeric($id) ? $role->find($id) : $role->where('type', $id)->first();
			}

			return response()->json($role, 200);
		}
		else
			abort(500, 'Impossible de créer le role');
    }

	/**
	 * Delete Role
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Request $request, $ressource_type, $ressource_id = null, $id = null) {
		// On inverse les bonnes variables
		if (is_null($id)) {
			if (is_null($ressource_id))
				list($id, $ressource_type) = [$ressource_type, $id];
			else
				list($id, $ressource_id) = [$ressource_id, $id];
		}

		$role = new Role;

		// On vérifie que la ressource possède des rôles
		if ($ressource_type || $request->only_for)
			$role = $role->where('only_for', $this->checkOnlyFor($ressource_type ? $ressource_type.($ressource_id ? '-'.$ressource_id : '') : $request->only_for));

		$role = is_numeric($id) ? $role->find($id) : $role->where('type', $id)->first();

	    if ($role) {
			if ($role->isDeletable()) {
				if ($role->delete())
					abort(204);
				else
					abort(500, "Impossible de supprimer le role souhaité");
			}
			else
				abort(403, "Il n'est pas autorisé de supprimer ce rôle (possiblement car déjà assigné ou rôles enfants attachés)");
		}
		else
			abort(404, "Role non trouvé");
    }
}
