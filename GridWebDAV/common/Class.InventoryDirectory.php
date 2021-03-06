<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/** Simian WebDAV service
 *
 * PHP version 5
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    SimianGrid
 * @author     John Hurliman <http://software.intel.com/en-us/blogs/author/john-hurliman/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */

class InventoryDirectory extends Sabre_DAV_Directory
{
    private $node;
    private $childNodes;
    private $fetched;

    function __construct($node)
    {
        $this->node = $node;
        $this->fetched = false; 
    }
    
    function initialize()
    {
        if (!$this->fetched)
        {
            // Fetch this folder directory and its children
            $this->childNodes = get_node_and_contents($this->node['ID'], $this->node['OwnerID']);
            $this->fetched = true;
        }
    }

    function getChildren()
    {
        $this->initialize();
        
        $children = array();
        $dirCount = 0;
        $fileCount = 0;
        
        if ($this->childNodes)
        {
            for ($i = 1; $i < count($this->childNodes); $i++)
            {
                $curNode = $this->childNodes[$i];
                
                if ($curNode['Type'] === 'Folder')
                {
                    $children[] = new InventoryDirectory($curNode);
                    ++$dirCount;
                }
                else
                {
                    $children[] = new InventoryFile($curNode);
                    ++$fileCount;
                }
            }
        }
        
        log_message('debug', "InventoryDirectory: Returning $dirCount directories and $fileCount files");
        return $children;
    }

    function getChild($name)
    {
        $this->initialize();
        
        // Some added security
        if ($name[0] == '.')
            throw new Sabre_DAV_Exception_FileNotFound('Access denied');
        
        if ($this->childNodes)
        {
            foreach ($this->childNodes as $child)
            {
                if ($child['Name'] === $name)
                {
                    if ($child['Type'] === 'Folder')
                        return new InventoryDirectory($child);
                    else
                        return new InventoryFile($child);
                }
            }
        }
        
        log_message('warn', "InventoryDirectory: The file with name: $name could not be found");
        throw new Sabre_DAV_Exception_FileNotFound("The file with name: $name could not be found");
    }

    function getName()
    {
        return $this->node['Name'];
    }
}
