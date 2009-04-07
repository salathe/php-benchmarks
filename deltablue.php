<?php

error_reporting(E_ALL | E_STRICT);

// Copyright 2008 the V8 project authors. All rights reserved.
// Copyright 1996 John Maloney and Mario Wolczko.

// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


// This implementation of the DeltaBlue benchmark is derived 
// from the v8 benchmark implementation which is an implementation
// of the Smalltalk implementation by John Maloney and Mario 
// Wolczko.

/* --- *
 * S t r e n g t h
 * --- */

/**
 * Strengths are used to measure the relative importance of constraints.
 * New strengths may be inserted in the strength hierarchy without
 * disrupting current constraints.  Strengths cannot be created outside
 * this class, so pointer comparison can be used for value comparison.
 */

class Strength
{
	var $strengthValue, $name;
	
	function __construct($strengthValue, $name)
	{
		$this->strengthValue = $strengthValue;
		$this->name = $name;		
	}
	
	function stronger($s1, $s2)
	{
		return $s1->strengthValue < $s2->strengthValue;
	}
	
	function weaker($s1, $s2)
	{
		return $s1->strengthValue > $s2->strengthValue;
	}
	
	function weakestOf($s1, $s2)
	{
		return $this->weaker($s1, $s2) ? $s1 : $s2;
	}
	
	function strongest($s1, $s2)
	{
		return $this->stronger($s1, $s2) ? $s1 : $s2;
	}
	
	function nextWeaker()
	{
		switch($this->strengthValue)
		{
		    case 0: return new Strength(6, "weakest");
		    case 1: return new Strength(5, "weakDefault");
		    case 2: return new Strength(4, "normal");
		    case 3: return new Strength(3, "strongDefault");
		    case 4: return new Strength(2, "preferred");
		    case 5: return new Strength(0, "required");
		}
	}
}

$REQUIRED = new Strength(0, "required");
	define('REQUIRED', $REQUIRED->strengthValue);

$STONG_PREFERRED = new Strength(1, "strongPreferred");
	define('STONG_PREFERRED', $STONG_PREFERRED->strengthValue);
	
$PREFERRED = new Strength(2, "preferred");
	define('PREFERRED', $PREFERRED->strengthValue);
	
$STRONG_DEFAULT	= new Strength(3, "strongDefault");
	define('STRONG_DEFAULT', $STRONG_DEFAULT->strengthValue);

$NORMAL = new Strength(4, "normal");
	define('NORMAL', $NORMAL->strengthValue);
	
$WEAK_DEFAULT = new Strength(5, "weakDefault");
	define('WEAK_DEFAULT', $WEAK_DEFAULT->strengthValue);
	
$WEAKEST = new Strength(6, "weakest");
	define('WEAKEST', $WEAKEST->strengthValue);

/* --- *
 * C o n s t r a i n t
 * --- */

/**
 * An abstract class representing a system-maintainable relationship
 * (or "constraint") between a set of variables. A constraint supplies
 * a strength instance variable; concrete subclasses provide a means
 * of storing the constrained variables and other information required
 * to represent a constraint.
 */
class Constraint
{
	var $strength;
	
	/**
	 * Done so the inherited class wont run the construct on inheritance
	 */
	function construct($strength)
	{
		$this->strength = $strength;
	}
	
	/**
	 * Activate this constraint and attempt to satisfy it.
	 */
	function addConstraint()
	{
		$this->addToGraph();
		
		// TODO
		//planner.incrementalAdd(this);
		$planner = new Planner;
		$planner->incrementalAdd($this);
	}
	
	/**
	 * Attempt to find a way to enforce this constraint. If successful,
	 * record the solution, perhaps modifying the current dataflow
	 * graph. Answer the constraint that this constraint overrides, if
	 * there is one, or nil, if there isn't.
	 * Assume: I am not already satisfied.
	 */
	function satisfy($mark)
	{
		$this->chooseMethod($mark);
		
		if(!$this->isSatisfied())
		{
		    if ($this->strength === REQUIRED)
		    {
		    	echo "Could not satisfy a required constraint!\n";
		    }
		      
		    return null;
		}
		
		$this->markInputs($mark);
		
		$out = $this->output();
		$overridden = $out->determinedBy;
		if($overridden !== null)
		{
			$overridden->markUnsatisfied();
		}
		
		$out->determinedBy = $this;
		
		// TODO
		$planner = new planner;
		
		if(!$planner->addPropagate($this, $mark))
		{
			echo "Cycle encountered";
		}
		
		$out->mark = $mark;
		
		return $overridden;
	}

	function destroyConstant()
	{
		if($this->isSatisfied())
		{
			$planner = new planner;
			$planner->incrementalRemove($this);
		}
		else 
		{
			$this->removeFromGraph();
		}
	}
	
	/**
	 * Normal constraints are not input constraints.  An input constraint
	 * is one that depends on external state, such as the mouse, the
	 * keybord, a clock, or some arbitraty piece of imperative code.
	 */
	function isInput()
	{
		return false;
	}
}

/* --- *
 * U n a r y   C o n s t r a i n t
 * --- */

/**
 * Abstract superclass for constraints having a single possible output
 * variable.
 */
class UnaryConstraint extends Constraint
{
	var $v, $strength, $myOutput, $satisfied;
	
	function construct($v, $strength)
	{
		Constraint::construct($strength);
		
		$this->myOutput = $v;
		$this->satisfied = false;
		$this->addConstraint();
	}
	
	/**
	 * Adds this constraint to the constraint graph
	 */
	function addToGraph()
	{
		$this->myOutput->addConstraint($this);
		$this->satisfied = false;
	}
	
	/**
	 * Decides if this constraint can be satisfied and records that
	 * decision.
	 */
	function chooseMethod($mark)
	{
		$this->satisfied = ($this->myOutput->mark != $mark) && Strength::stronger($this->strength, $this->myOutput->walkStrength);
	}

	/**
	 * Returns true if this constraint is satisfied in the current solution.
	 */
	function isSatisfied()
	{
		return $this->satisfied;
	}
	
	function markInputs($mark)
	{
		// has no inputs
	}
	
	/**
	 * Returns the current output variable.
	 */
	function output()
	{
		return $this->myOutput;
	}

	/**
	 * Calculate the walkabout strength, the stay flag, and, if it is
	 * 'stay', the value for the current output of this constraint. Assume
	 * this constraint is satisfied.
	 */
	function recalculate()
	{
		$this->myOutput->walkStrength = $this->strength;
		$this->myOutput->stay = !$this->isInput();
		
		if($this->myOutput->stay)
		{
			$this->execute(); // Stay optimization
		}
	}

	/**
	 * Records that this constraint is unsatisfied
	 */
	function markUnsatisfied()
	{
		$this->satisfied = false;
	}
	
	function inputsKnown()
	{
		return true;
	}
	
	function removeFromGraph()
	{
		if($this->myOutput != null)
		{
			$this->myOutput->removeConstraint($this);
			$this->satisfied = false;
		}
	}	
}

/* --- *
 * S t a y   C o n s t r a i n t
 * --- */

/**
 * Variables that should, with some level of preference, stay the same.
 * Planners may exploit the fact that instances, if satisfied, will not
 * change their output during plan execution.  This is called "stay
 * optimization".
 */
class StayConstraint extends UnaryConstraint 
{
	var $v, $str;
	function construct($v, $str)
	{
		UnaryConstraint::construct($v, $str);
	}
	
	function execute()
	{
		// Stay constraints do nothing
	}
}


/* --- *
 * E d i t   C o n s t r a i n t
 * --- */

/**
 * A unary input constraint used to mark a variable that the client
 * wishes to change.
 */
class EditConstraint extends UnaryConstraint 
{
	var $v, $str;
	function construct($v, $str)
	{
		UnaryConstraint::construct($v, $str);
	}
	
	/**
	 * Edits indicate that a variable is to be changed by imperative code.
	 */	
	function isInput()
	{
		return true;	
	}
	
	function execute()
	{
		// Edit constraints do nothing
	}
}