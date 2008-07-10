<?php
/**
 * Provides session-based messaging for page-to-page communication
 * 
 * @copyright  Copyright (c) 2007-2008 William Bond
 * @author     William Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fMessaging
 * 
 * @version    1.0.0b
 * @changes    1.0.0b  The initial implementation [wb, 2008-03-05]
 */
class fMessaging
{
	/**
	 * Creates a message that is stored in the session and retrieved by another page
	 * 
	 * @param  string $name       A name for the message
	 * @param  string $message    The message to send
	 * @param  string $recipient  The intended recipient
	 * @return void
	 */
	static public function create($name, $message, $recipient)
	{                                         
		fSession::set($name, $message, __CLASS__ . '::' . $recipient . '::');
	}
	
	
	/**
	 * Retrieves and removes a message from the session
	 * 
	 * @param  string $name       The name of the message to retrieve
	 * @param  string $recipient  The intended recipient
	 * @return string  The message contents
	 */
	static public function retrieve($name, $recipient)
	{
		$prefix  = __CLASS__ . '::' . $recipient . '::';
		$message = fSession::get($name, NULL, $prefix);
		fSession::clear($name, $prefix);
		return $message;
	}
	
	
	/**
	 * Retrieves a message, removes it from the session and prints it with the CSS class specified - will not print if no content
	 * 
	 * The message will be printed in a paragraph tag if it does not contain
	 * any block level HTML. Otherwise it will be printed in a div tag.
	 * 
	 * @param  string $name       The name of the message to retrieve
	 * @param  string $recipient  The intended recipient
	 * @return void
	 */
	static public function show($name, $recipient, $css_class='')
	{
		fHTML::show(self::retrieve($name, $recipient), $css_class);
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fMessaging
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2007-2008 William Bond <will@flourishlib.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */