--TEST--
basic tests for different types of if and expression's casting to bool
--FILE--
<?php
if(true)
{
	echo "true\n";
}
if(false)
{
	echo "wrong!\n";
}

if(true)
{
	echo "true\n";
}
else
{
	echo "wrong!\n";
}

if(false)
{
	echo "wrong!\n";
}
else
{
	echo "else\n";
}

if(0)
{
	echo "wrong!\n";
}

if(1)
{
	echo "true\n";
}

if(true)
{
	if(false)
	{
		echo "wrong!\n";
	}
	else
	{
		if(true)
		{
			echo "truee\n";
		}
		else
		{
			echo "wrong!\n";
		}
	}
echo "yo\n";
}

if(true)
{
	echo "true\n";
}


?>
--EXPECT--
true
true
else
true
truee
yo
true
