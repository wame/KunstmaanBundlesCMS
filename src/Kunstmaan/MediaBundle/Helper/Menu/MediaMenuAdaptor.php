<?php

namespace Kunstmaan\MediaBundle\Helper\Menu;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Kunstmaan\AdminBundle\Helper\Menu\MenuItem;
use Kunstmaan\AdminBundle\Helper\Menu\MenuAdaptorInterface;
use Kunstmaan\AdminBundle\Helper\Menu\MenuBuilder;
use Kunstmaan\AdminBundle\Helper\Menu\TopMenuItem;
use Symfony\Component\Translation\Translator;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Knp\Menu\ItemInterface as KnpMenu;
use Kunstmaan\MediaBundle\Entity\Media;
use Kunstmaan\MediaBundle\Entity\Folder;

/**
 * The Media Menu Adaptor
 */
class MediaMenuAdaptor implements MenuAdaptorInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * In this method you can add children for a specific parent, but also remove and change the already created children
     *
     * @param MenuBuilder $menu      The MenuBuilder
     * @param MenuItem[]  &$children The current children
     * @param MenuItem    $parent    The parent Menu item
     * @param Request     $request   The Request
     */
    public function adaptChildren(MenuBuilder $menu, array &$children, MenuItem $parent = null, Request $request = null)
    {

        $mediaRoutes = array(
            'Show media' => 'KunstmaanMediaBundle_media_show',
            'Edit metadata' => 'KunstmaanMediaBundle_metadata_edit',
            'Edit slide' => 'KunstmaanMediaBundle_slide_edit',
            'Edit video' => 'KunstmaanMediaBundle_video_edit'
        );

        $createRoutes = array(
            'Create slide' => 'KunstmaanMediaBundle_folder_slidecreate',
            'Create video' => 'KunstmaanMediaBundle_folder_videocreate',
            'Create image' => 'KunstmaanMediaBundle_folder_imagecreate',
            'Create file' => 'KunstmaanMediaBundle_folder_filecreate',
            'Bulk upload' => 'KunstmaanMediaBundle_folder_bulkupload'
        );

        $allRoutes = array_merge($createRoutes, $mediaRoutes);

        if (is_null($parent)) {
            /* @var Folder[] $galleries */
            $galleries = $this->em->getRepository('KunstmaanMediaBundle:Folder')->getAllFolders();
            $currentId = $request->get('folderId');

            if (isset($currentId)) {
                /* @var Folder $currentGallery */
                $currentGallery = $this->em->getRepository('KunstmaanMediaBundle:Folder')->findOneById($currentId);
            } else if (in_array($request->attributes->get('_route'), $mediaRoutes)) {
                /* @var Media $media */
                $media     = $this->em->getRepository('KunstmaanMediaBundle:Media')->getMedia($request->get('mediaId'));
                $currentGallery = $media->getGallery();
            } else if (in_array($request->attributes->get('_route'), $createRoutes)) {
                $currentId = $request->get('gallery_id');
                if (isset($currentId)) {
                    $currentGallery = $this->em->getRepository('KunstmaanMediaBundle:Folder')->findOneById($currentId);
                }
            }

            if (isset($currentGallery)) {
                $parents = $currentGallery->getParents();
            } else {
                $parents = array();
            }

            foreach ($galleries as $folder) {
                $menuitem = new TopMenuItem($menu);
                $menuitem->setRoute('KunstmaanMediaBundle_folder_show');
                $menuitem->setRouteparams(array('folderId' => $folder->getId(), 'slug' => $folder->getSlug()));
                $menuitem->setInternalname($folder->getName());
                $menuitem->setParent($parent);
                $menuitem->setRole($folder->getRel());
                if (isset($currentGallery) && (stripos($request->attributes->get('_route'), $menuitem->getRoute()) === 0 || in_array($request->attributes->get('_route'), $allRoutes))) {
                    if ($currentGallery->getId() == $folder->getId()) {
                        $menuitem->setActive(true);
                    } else {
                        foreach ($parents as $parent) {
                            if ($parent->getId() == $folder->getId()) {
                                $menuitem->setActive(true);
                                break;
                            }
                        }
                    }
                }
                $children[] = $menuitem;
            }
        } else if ('KunstmaanMediaBundle_folder_show' == $parent->getRoute()) {
            $parentRouteParams = $parent->getRouteparams();
            /* @var Folder $parentGallery */
            $parentGallery = $this->em->getRepository('KunstmaanMediaBundle:Folder')->findOneById($parentRouteParams['folderId']);
            /* @var Folder[] $galleries */
            $galleries = $parentGallery->getChildren();
            $currentId = $request->get('folderId');

            if (isset($currentId)) {
                /* @var Folder $currentGallery */
                $currentGallery = $this->em->getRepository('KunstmaanMediaBundle:Folder')->findOneById($currentId);
            } else if (in_array($request->attributes->get('_route'), $mediaRoutes)) {
                $media     = $this->em->getRepository('KunstmaanMediaBundle:Media')->getMedia($request->get('mediaId'));
                $currentGallery = $media->getGallery();
            } else if (in_array($request->attributes->get('_route'), $createRoutes)) {
                $currentId = $request->get('folderId');
                if (isset($currentId)) {
                    $currentGallery = $this->em->getRepository('KunstmaanMediaBundle:Folder')->findOneById($currentId);
                }
            }

            /* @var Folder[] $parentGalleries */
            $parentGalleries = null;
            if (isset($currentGallery)) {
                $parentGalleries = $currentGallery->getParents();
            } else {
                $parentGalleries = array();
            }

            foreach ($galleries as $folder) {
                $menuitem = new MenuItem($menu);
                $menuitem->setRoute('KunstmaanMediaBundle_folder_show');
                $menuitem->setRouteparams(array('folderId' => $folder->getId(), 'slug' => $folder->getSlug()));
                $menuitem->setInternalname($folder->getName());
                $menuitem->setParent($parent);
                $menuitem->setRole($folder->getRel());
                if (isset($currentGallery) && (stripos($request->attributes->get('_route'), $menuitem->getRoute()) === 0 || in_array($request->attributes->get('_route'), $allRoutes))) {
                    if ($currentGallery->getId() == $folder->getId()) {
                        $menuitem->setActive(true);
                    } else {
                        foreach ($parentGalleries as $parentGallery) {
                            if ($parentGallery->getId() == $folder->getId()) {
                                $menuitem->setActive(true);
                                break;
                            }
                        }
                    }
                }
                $children[] = $menuitem;
            }

            foreach ($allRoutes as $name => $route) {
                $menuitem = new MenuItem($menu);
                $menuitem->setRoute($route);
                $menuitem->setInternalname($name);
                $menuitem->setParent($parent);
                $menuitem->setAppearInNavigation(false);
                if (stripos($request->attributes->get('_route'), $menuitem->getRoute()) === 0) {
                    if (stripos($menuitem->getRoute(), 'KunstmaanMediaBundle_media_show') === 0) {
                        /* @var Media $media */
                        $media     = $this->em->getRepository('KunstmaanMediaBundle:Media')->getMedia($request->get('mediaId'));
                        $menuitem->setInternalname('Show ' . $media->getClassType() . ' ' . $media->getName());
                    }
                    $menuitem->setActive(true);
                }

                $children[] = $menuitem;
            }

        }

    }
}